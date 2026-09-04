FROM php:8.4-cli-bookworm

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates curl gnupg git unzip \
        libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
        libldap2-dev libpq-dev libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# pdo_sqlite and sqlite3 ship enabled in the base image.
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath gd intl ldap pdo_mysql pdo_pgsql zip

# Microsoft ODBC driver, then the SQL Server PDO driver on top of it.
# prod.list pins signed-by=/usr/share/keyrings/microsoft-prod.gpg, so the key
# has to be dearmored to exactly that path.
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
        | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && curl -fsSL https://packages.microsoft.com/config/debian/12/prod.list \
        -o /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18 unixodbc-dev \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# The project is bind mounted from the host, so its files are owned by the host
# user while the container runs as root. Git refuses to read a repository owned
# by someone else, which makes Composer emit a scary but harmless fatal.
RUN git config --system --add safe.directory /app

# Node for the Vite asset build.
COPY --from=node:20-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:20-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

WORKDIR /app
