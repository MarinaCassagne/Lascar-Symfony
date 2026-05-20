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

# Copier uniquement les fichiers de dépendances
COPY composer.json composer.lock ./

# Installer les dépendances (sans scripts, le projet n'est pas encore monté)
RUN composer install --no-scripts --optimize-autoloader

# Exposer le port
EXPOSE 8000

# Script de démarrage : installe le vendor si absent, puis lance le serveur
CMD ["sh", "-c", "composer install --no-scripts && php -S 0.0.0.0:8000 -t public"]
