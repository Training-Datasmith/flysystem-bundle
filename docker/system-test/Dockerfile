FROM php:8.2-cli-bookworm@sha256:26b388d187a5e592ef5e02c14d0f92dd9c761894ff0a5fe338183196471c4b4e

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" ftp zip \
    && curl -fsSL -o /tmp/mongodb-2.5.7.tgz https://pecl.php.net/get/mongodb-2.5.7.tgz \
    && echo "078773856f4066b56738fc13950bb7ee16b5e66f727fb41d3fbec6043c78fd32  /tmp/mongodb-2.5.7.tgz" | sha256sum -c - \
    && pecl install /tmp/mongodb-2.5.7.tgz \
    && docker-php-ext-enable mongodb \
    && rm -f /tmp/mongodb-2.5.7.tgz \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
