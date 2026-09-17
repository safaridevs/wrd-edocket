/*
 * E-Docket -- build, test and deploy as a Docker container.
 *
 * Modelled on ose_wrats2.groovy: a dedicated Linux build agent that is never a
 * deploy target, a tagged image shipped over ssh, per-environment compose files
 * under deploy/. Secrets are Jenkins credentials (edocket-<env>-*) rendered into
 * the committed deploy/.env_<ENV> template with envsubst, as wrats2 does, and
 * shipped to the host as a 0600 .env (deploy/SECRETS.md in the wrd-edocket
 * repo). Host prerequisites and the deploy flow are in deploy/README.md.
 *
 * Environment -> server. QAT and UAT share the non-prod host with wrats2;
 * E-Docket binds 8011/8012 per ~/ose-engineering/port-register.md
 * (deploy/.env_docker_compose_<env>).
 *
 * Job setup: "Pipeline script from SCM" on this repository, script path Jenkinsfile,
 * branch specifier ${BRANCH} (the job parameter below), so the Jenkinsfile and the code
 * it builds always come from the same commit. This file used to be
 * Jenkins_Pipelines/ose_edocket.groovy; it lives here because the pipeline is part of the
 * application (github-migration-checklist.md, 16 Sep 2026).
 */
def DEPLOY_SERVER_MAP = [
        QAT: '10.64.85.45',
        UAT: '10.64.85.45',
]
pipeline {
    // Top-level agent is `none` so the Linux build stages and the Windows
    // deployment-log stage can each claim their own agent.
    agent none

    parameters {
        string(name: 'BRANCH', defaultValue: 'main',
               description: 'Branch to build, or refs/tags/<tag> to build a pinned release. The job\'s branch specifier is ${BRANCH}, so this selects the Jenkinsfile too.')
        choice(name: 'deploy_environment', choices: ['QAT', 'UAT'])
        booleanParam(name: 'Deploy', defaultValue: false,
                     description: 'Ship the image to the environment and restart it. Unchecked = build + test only.')
        booleanParam(name: 'RUN_MIGRATIONS', defaultValue: true,
                     description: 'Run `php artisan migrate --force` against the target database before starting the new container. Only applies when Deploy is true.')
    }

    environment {
        IMAGE_NAME  = 'edocket-app'
        // Every containerised app lives under /opt/apps/<app>/<env> (port-register.md).
        DEPLOY_PATH = '/opt/apps/edocket'
    }

    options {
        timeout(time: 30, unit: 'MINUTES')
        durabilityHint('PERFORMANCE_OPTIMIZED')
    }

    stages {

        stage('Build & Deploy (Linux)') {
            agent { label 'app-healthcheck' }

            stages {

                stage('Resolve target') {
                    steps {
                        script {
                            def envLower = params.deploy_environment.toLowerCase()

                            env.DEPLOY_SERVER   = DEPLOY_SERVER_MAP[params.deploy_environment]
                            env.DEPLOY_ENV_PATH = envLower
                            env.REMOTE_PATH     = "${env.DEPLOY_PATH}/${envLower}"
                            env.COMPOSE_SRC     = "deploy/${envLower}.docker-compose.yaml"
                            env.COMPOSE_ENV_SRC = "deploy/.env_docker_compose_${envLower}"
                            env.APP_ENV_SRC     = "deploy/.env_${params.deploy_environment}"

                            if (!env.DEPLOY_SERVER) {
                                error "No DEPLOY_SERVER defined for ${params.deploy_environment}"
                            }
                            echo "Resolved: server=${env.DEPLOY_SERVER} remotePath=${env.REMOTE_PATH} compose=${env.COMPOSE_SRC} composeEnv=${env.COMPOSE_ENV_SRC} appEnv=${env.APP_ENV_SRC}"
                        }
                    }
                }

                stage('Checkout') {
                    steps {
                        script {
                            // The same revision Jenkins read this Jenkinsfile from: `checkout scm`
                            // reuses the job's SCM configuration, resolved branch and all. The
                            // agent workspace needs it because the Jenkinsfile itself is fetched
                            // on the controller (lightweight checkout).
                            checkout scm

                            def shortSha = sh(returnStdout: true, script: 'git rev-parse --short HEAD').trim()
                            // Surfaced in the app as config('app.version').
                            env.APP_VERSION_STAMP = "${params.BRANCH}-${shortSha}"
                            // Docker tags cannot hold '/' or ':' (feature/x, refs/tags/x).
                            def safeRef = params.BRANCH.replaceFirst(/^refs\/tags\//, '').replaceAll(/[^A-Za-z0-9_.-]/, '_')
                            env.IMAGE_TAG = "${safeRef}-${shortSha}"
                            env.IMAGE_REF = "${env.IMAGE_NAME}:${env.IMAGE_TAG}"
                            echo "APP_VERSION -> ${env.APP_VERSION_STAMP}   image -> ${env.IMAGE_REF}"
                        }
                    }
                }

                stage('Resolve .env') {
                    steps {
                        // The committed deploy/.env_<ENV> holds '${VAR}' placeholders for
                        // the secret values; render them from the edocket-<env>-* Jenkins
                        // credentials. Runs on every build, so a build-only run proves the
                        // credentials exist. See deploy/SECRETS.md.
                        withCredentials([
                            string(credentialsId: "edocket-${env.DEPLOY_ENV_PATH}-app-key",              variable: 'APP_KEY'),
                            string(credentialsId: "edocket-${env.DEPLOY_ENV_PATH}-db-password",          variable: 'DB_PASSWORD'),
                            string(credentialsId: "edocket-${env.DEPLOY_ENV_PATH}-ldap-username",        variable: 'LDAP_USERNAME'),
                            string(credentialsId: "edocket-${env.DEPLOY_ENV_PATH}-ldap-password",        variable: 'LDAP_PASSWORD'),
                            string(credentialsId: "edocket-${env.DEPLOY_ENV_PATH}-bounce-mail-password", variable: 'BOUNCE_MAIL_PASSWORD'),
                        ]) {
                            // Single-quoted so Groovy interpolates nothing. set +x so
                            // rendered content never reaches the build log (credential
                            // masking is exact-string only).
                            sh '''
                                set +x
                                set -e
                                SECRET_VARS="APP_KEY DB_PASSWORD LDAP_USERNAME LDAP_PASSWORD BOUNCE_MAIL_PASSWORD"
                                [ -f "${APP_ENV_SRC}" ] || { echo "Missing ${APP_ENV_SRC}"; exit 1; }
                                command -v envsubst >/dev/null || {
                                    echo "envsubst not found on agent - install gettext-base"; exit 1;
                                }
                                # The template single-quotes each placeholder, which phpdotenv
                                # reads literally; a value holding ' or a newline cannot be
                                # represented that way. Names only are printed, never values.
                                for v in $SECRET_VARS; do
                                    val=$(printenv "$v")
                                    [ -n "$val" ] || { echo "ERROR: credential for $v is empty"; exit 1; }
                                    case "$val" in
                                        *"'"*) echo "ERROR: credential for $v contains a single quote"; exit 1 ;;
                                    esac
                                    [ "$(printenv "$v" | wc -l)" -le 1 ] || { echo "ERROR: credential for $v contains a newline"; exit 1; }
                                done
                                # Guard: a placeholder in the template that is not in SECRET_VARS
                                # (nor the literal APP_NAME reference) would ship unrendered.
                                # Checked on the template, not the output, because a secret
                                # value may itself contain '${'.
                                unknown=$(grep -vE '^[[:space:]]*#' "${APP_ENV_SRC}" | grep -oE '[$][{][A-Z0-9_]+[}]' | tr -d '${}' | sort -u | \
                                    while read -r n; do case " $SECRET_VARS APP_NAME " in *" $n "*) ;; *) echo "$n" ;; esac; done)
                                if [ -n "$unknown" ]; then
                                    echo "ERROR: placeholder(s) with no credential bound: $unknown"; exit 1
                                fi
                                umask 077
                                # Substitute ONLY the secret vars: the template keeps literal
                                # ${APP_NAME} references that a bare envsubst would blank out.
                                envsubst "$(for v in $SECRET_VARS; do printf '${%s} ' "$v"; done)" \
                                    < "${APP_ENV_SRC}" > .env
                                echo "APP_VERSION=${APP_VERSION_STAMP}" >> .env
                                echo "Rendered .env from ${APP_ENV_SRC}"
                            '''
                        }
                    }
                }

                stage('Build image') {
                    steps {
                        sh '''
                            set -e
                            docker --version
                            docker compose version
                            # QAT/UAT keep dev dependencies so phpunit is in the image.
                            # A PROD build would pass --build-arg COMPOSER_INSTALL_FLAGS=--no-dev.
                            docker build \
                                --build-arg COMPOSER_INSTALL_FLAGS="" \
                                -t "${IMAGE_REF}" .
                        '''
                    }
                }

                stage('Test') {
                    steps {
                        // The suite runs against in-memory SQLite (phpunit.xml) with a
                        // throwaway APP_KEY. The rendered .env is deliberately NOT passed:
                        // real environment variables would override phpunit.xml's <env>
                        // entries and point the tests at the environment's database.
                        // Only reports/ is mounted, not the workspace, so the tests can
                        // neither see the rendered .env nor test anything but the image.
                        sh '''
                            set -e
                            mkdir -p reports
                            TEST_KEY="base64:$(openssl rand -base64 32)"
                            docker run --rm \
                              --entrypoint "" \
                              -w /var/www/html \
                              -e APP_ENV=testing \
                              -e APP_KEY="${TEST_KEY}" \
                              -v "${WORKSPACE}/reports:/reports" \
                              "${IMAGE_REF}" \
                              sh -c "php vendor/bin/phpunit --log-junit /reports/phpunit_${BUILD_NUMBER}_edocket.xml"
                        '''
                    }
                }

                stage('Ship config to server') {
                    when { expression { params.Deploy } }
                    steps {
                        sh '''
                            set -e
                            for f in .env "${COMPOSE_SRC}" "${COMPOSE_ENV_SRC}"; do
                                [ -f "$f" ] || { echo "Missing $f"; exit 1; }
                            done
                            # The per-env directory is created here (jenkins-owned, fine).
                            # documents/ and logs/ are NOT: Docker creates them on first
                            # start and the container entrypoint chowns them to www-data.
                            ssh -o StrictHostKeyChecking=no jenkins@${DEPLOY_SERVER} "mkdir -p ${REMOTE_PATH}"
                            # The rendered .env is streamed, not scp'd, so it is created 0600
                            # under umask 077 (scp keeps an existing file's mode) and swapped in
                            # atomically. The container's entrypoint reads it as root.
                            ssh -o StrictHostKeyChecking=no jenkins@${DEPLOY_SERVER} \
                                "umask 077 && cat > ${REMOTE_PATH}/.env.new && mv -f ${REMOTE_PATH}/.env.new ${REMOTE_PATH}/.env" < .env
                            scp -o StrictHostKeyChecking=no "${COMPOSE_SRC}"     jenkins@${DEPLOY_SERVER}:${REMOTE_PATH}/docker-compose.yaml
                            scp -o StrictHostKeyChecking=no "${COMPOSE_ENV_SRC}" jenkins@${DEPLOY_SERVER}:${REMOTE_PATH}/.env_docker_compose
                        '''
                    }
                }

                stage('Ship image to server') {
                    when { expression { params.Deploy } }
                    steps {
                        sh '''
                            set -e
                            # No pipefail (the agent's sh may be dash): a failed save still
                            # fails the step because docker load rejects the truncated stream.
                            docker save "${IMAGE_REF}" | gzip | \
                                ssh -o StrictHostKeyChecking=no jenkins@${DEPLOY_SERVER} 'gunzip | docker load'
                        '''
                    }
                }

                stage('Run migrations') {
                    when { expression { params.Deploy && params.RUN_MIGRATIONS } }
                    steps {
                        // Runs on the deploy host (which has the database network path),
                        // with the new image, before the old container is stopped.
                        sh '''
                            set -e
                            ssh -o StrictHostKeyChecking=no jenkins@${DEPLOY_SERVER} \
                                bash -s "${REMOTE_PATH}" "${IMAGE_TAG}" < deploy/remote/migrate.sh
                        '''
                    }
                }

                stage('Start containers') {
                    when { expression { params.Deploy } }
                    steps {
                        sh '''
                            set -e
                            ssh -o StrictHostKeyChecking=no jenkins@${DEPLOY_SERVER} \
                                bash -s "${REMOTE_PATH}" "${IMAGE_TAG}" < deploy/remote/start.sh
                        '''
                    }
                }

                stage('Cleanup deploy server') {
                    when { expression { params.Deploy } }
                    steps {
                        sh '''
                            ssh -o StrictHostKeyChecking=no jenkins@${DEPLOY_SERVER} \
                                bash -s < deploy/remote/cleanup.sh || true
                        '''
                    }
                }
            }

            // Agent-scoped post block: junit needs the workspace, and the docker
            // cleanup needs the build agent.
            post {
                always {
                    junit allowEmptyResults: true, testResults: "reports/phpunit_${BUILD_NUMBER}_*.xml"
                    sh '''
                        set +e
                        rm -f .env
                        echo "Removing build image and dangling layers"
                        docker ps -aq --filter "ancestor=${IMAGE_REF}" | xargs -r docker rm -f
                        docker rmi "${IMAGE_REF}" || true
                        docker image prune -f || true
                    '''
                }
            }
        }

        /* ============================
         * WINDOWS: deployment log
         * ============================ */
        stage('JSON Output') {
            agent { label 'unifiedAppQat' }
            when { expression { params.Deploy } }
            steps {
                script {
                    def jsonRecord = [
                            Date: new Date().format("yyyy-MM-dd HH:mm:ss"),
                            Application: 'E-Docket',
                            Environment: params.deploy_environment,
                            Version: env.APP_VERSION_STAMP ?: params.BRANCH
                    ]
                    writeFile(file: 'deployments_temp.json', text: groovy.json.JsonOutput.toJson(jsonRecord))
                    bat '''
                        type deployments_temp.json >> \\\\unifiedappqat\\deployments\\deployments.json
                    '''
                }
            }
        }
    }

    post {
        failure {
            echo 'Pipeline failed'
        }
        success {
            echo 'Pipeline completed successfully'
        }
    }
}
