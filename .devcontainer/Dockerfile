FROM php:8.4-cli

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    git \
    unzip \
    curl \
    wget \
    gnupg \
    ca-certificates \
    zip \
    libzip-dev \
    libxml2-dev \
    libicu-dev \
    libonig-dev \
    build-essential \
  && docker-php-ext-install pdo pdo_mysql mbstring zip intl xml \
  && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
  && curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
  && apt-get install -y --no-install-recommends nodejs \
  && npm --version || true \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /workspace

CMD ["bash"]
