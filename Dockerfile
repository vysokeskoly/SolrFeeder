ARG REGISTRY="972379852442.dkr.ecr.eu-central-1.amazonaws.com"
ARG DOMAIN="vscz"

FROM ${REGISTRY}/${DOMAIN}-base:common-8.1

ARG BUILD_NUMBER=undefined
ARG GIT_REPO=undefined
ARG GIT_BRANCH=undefined
ARG GIT_COMMIT=undefined
ARG PRIVATE_FEED_USER

ENV PRIVATE_FEED_USER=${PRIVATE_FEED_USER}

WORKDIR /app

# Copy dependency files
COPY composer.json composer.lock ./

# Copy application files
COPY bin/solr-feeder-console ./bin/solr-feeder-console
COPY src ./src

# Copy git for build metadata
COPY .git ./.git

# Install dependencies, build application, and prepare runtime
RUN --mount=type=secret,id=PRIVATE_FEED_PASS \
    PRIVATE_FEED_PASS="$(cat /run/secrets/PRIVATE_FEED_PASS)" \
    && COMPOSER_AUTH="{\"github-oauth\": {\"github.com\": \"${PRIVATE_FEED_PASS}\"}}" \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist --optimize-autoloader \
    && composer dump-autoload --optimize --no-dev \
    # Clean vendor test/docs directories to reduce image size
    && find vendor/symfony -type d -name Tests -exec rm -rf {} + 2>/dev/null; \
    rm -rf vendor/twig/twig/test \
           vendor/monolog/monolog/tests

# Bake the Symfony command into the entrypoint so the image has a single purpose.
# CMD supplies the default config path - mount your ConfigMap there, or override
# via `args:` in the CronJob spec if you need a different path.
ENTRYPOINT ["/app/bin/solr-feeder-console", "solr-feeder:feed"]
CMD ["/etc/solr-feeder/config.xml"]
