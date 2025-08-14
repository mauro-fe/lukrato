FROM php:8.2-apache

# Instala extensões necessárias do PHP
RUN apt-get update && apt-get install -y \
    libicu-dev \
    && docker-php-ext-install intl pdo pdo_mysql

# Habilita reescrita no Apache
RUN a2enmod rewrite

# Define variáveis de ambiente
ENV PORT=8080
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Configura o Apache para apontar para /public e escutar na porta 8080
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf && \
    sed -i 's|Listen 80|Listen 8080|g' /etc/apache2/ports.conf

# Copia os arquivos do projeto
COPY . /var/www/html

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
