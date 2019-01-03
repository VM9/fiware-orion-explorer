FROM php:latest

RUN apt update && \
apt install git gnupg -y && \
curl -sL https://deb.nodesource.com/setup_9.x |  bash - && \
apt install nodejs -y

RUN git clone https://github.com/VM9/fiware-orion-explorer 
WORKDIR /fiware-orion-explorer 
RUN curl -sS https://getcomposer.org/installer |  php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev
RUN npm install 
RUN npm install bower -g 
RUN bower install --allow-root

CMD ["php", "-S", "0.0.0.0:7000"]
