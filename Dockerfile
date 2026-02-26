# Image PHP avec FPM
FROM php:8.4-fpm

# Installer dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    zip \
    curl \
    && docker-php-ext-install intl pdo pdo_mysql zip

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le dossier de travail
WORKDIR /var/www

# Copier le projet
COPY . .

# Installer les dépendances Symfony
RUN composer install

# Exposer le port
EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]