FROM php:7.1.29

RUN apt update && \
apt install git gnupg -y && \
curl -sL https://deb.nodesource.com/setup_9.x |  bash - && \
apt install nodejs -y

RUN curl -sS https://getcomposer.org/installer |  php -- --install-dir=/usr/local/bin --filename=composer --version=1.8.0

WORKDIR /fiware-orion-explorer
COPY . .

RUN composer install --no-dev
RUN npm install
RUN npm install bower -g
RUN bower install --allow-root

CMD ["php", "-S", "0.0.0.0:7000"]
