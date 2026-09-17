# syntax=docker/dockerfile:1
#
# Deployable E-Docket image: Apache + PHP 8.4 serving public/, with a queue
# worker and the Laravel scheduler run by supervisord. Built by
# the repository Jenkinsfile and shipped to the QAT/UAT hosts as a
# tagged image (see deploy/README.md).
#
# This is NOT the local development harness -- that is docker/php/Dockerfile,
# used by docker-compose.yml with a bind-mounted tree and `artisan serve`.

FROM php:8.4-apache-bookworm

# Empty for QAT/UAT (phpunit stays in the image so the pipeline can run the
# suite); "--no-dev" for a production build.
ARG COMPOSER_INSTALL_FLAGS=""
# OCRmyPDF + Tesseract add ~400MB. documents:index-text degrades gracefully
# without them, so they are opt-in.
ARG WITH_OCR=false

ENV DEBIAN_FRONTEND=noninteractive
WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# System packages
#   python3 + pdfrw, poppler-utils : tools/pdf/convert.py and pdftotext, which
#                                    PdfConversionService / DocumentTextExtraction
#                                    shell out to. pdfrw is not packaged in
#                                    bookworm, so it comes from pip.
#   supervisor                    : apache + queue worker + scheduler in one
#                                   container.
# ---------------------------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates curl gnupg git unzip \
        libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
        libldap2-dev \
        python3 python3-pip poppler-utils \
        supervisor \
    && rm -rf /var/lib/apt/lists/*

COPY tools/pdf/requirements.txt /tmp/pdf-requirements.txt
RUN pip3 install --no-cache-dir --break-system-packages -r /tmp/pdf-requirements.txt \
    && rm /tmp/pdf-requirements.txt

RUN if [ "$WITH_OCR" = "true" ]; then \
        apt-get update \
        && apt-get install -y --no-install-recommends ocrmypdf tesseract-ocr-eng \
        && rm -rf /var/lib/apt/lists/*; \
    fi

# ---------------------------------------------------------------------------
# PHP extensions. pdo_sqlite ships enabled in the base image and is what the
# test suite uses; sqlsrv/pdo_sqlsrv is the production driver.
# ---------------------------------------------------------------------------
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl ldap opcache zip

# Microsoft ODBC driver, then the SQL Server PDO driver on top of it.
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
        | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && curl -fsSL https://packages.microsoft.com/config/debian/12/prod.list \
        -o /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18 unixodbc-dev \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-edocket.ini

# ---------------------------------------------------------------------------
# Apache: serve public/, honour Laravel's public/.htaccess.
# ---------------------------------------------------------------------------
RUN a2enmod rewrite headers
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# ---------------------------------------------------------------------------
# Build tooling: Composer, and Node for the Vite asset build. Node is removed
# again after the build (it is only needed to produce public/build).
# ---------------------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=node:20-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:20-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

# Dependencies first so the layer caches while application code changes.
COPY composer.json composer.lock ./
RUN composer install ${COMPOSER_INSTALL_FLAGS} \
        --no-scripts --no-autoloader --no-interaction --prefer-dist --no-progress

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

# ---------------------------------------------------------------------------
# Application code (see .dockerignore for what is left out: .env files, vendor,
# node_modules, storage contents, the dev compose harness).
# ---------------------------------------------------------------------------
COPY . .

# tailwind.config.js scans vendor/laravel/.../Pagination views, so the asset
# build has to happen after composer install, in this stage.
RUN composer dump-autoload --optimize $( [ -n "$COMPOSER_INSTALL_FLAGS" ] && echo "--no-dev" ) \
    && php artisan package:discover --ansi \
    && NODE_OPTIONS="--max-old-space-size=2048" npm run build \
    && rm -rf node_modules /usr/local/lib/node_modules /usr/local/bin/node /usr/local/bin/npm \
    && php artisan storage:link \
    && mkdir -p storage/app/private storage/app/public storage/logs \
            storage/framework/cache storage/framework/sessions storage/framework/views \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/supervisord.conf /etc/supervisor/conf.d/edocket.conf
COPY docker/entrypoint.sh /usr/local/bin/edocket-entrypoint
RUN chmod +x /usr/local/bin/edocket-entrypoint

# The application .env is rendered by Jenkins from its credentials and
# bind-mounted at /run/secrets/edocket.env (host file 0600). The entrypoint
# copies it into a tmpfs at /run/edocket as root:www-data 0640; this symlink is
# where Laravel looks for it. It dangles when nothing is mounted, which is
# fine: Dotenv treats a missing .env as "use the environment" (the Test stage
# relies on that). See deploy/SECRETS.md.
RUN mkdir -p /run/edocket && ln -s /run/edocket/.env /var/www/html/.env

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://localhost/up >/dev/null || exit 1

ENTRYPOINT ["/usr/local/bin/edocket-entrypoint"]
