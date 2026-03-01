# Définition d'une image de base sur laquelle créer une image personnalisée
FROM php:8.4.18-apache

# Installation des dépendances système (utile pour faire fonctionner le language utiliser ici php)
RUN apt-get update && apt-get install -y\
    git \
    unzip \
    libicu-dev \
    libonig-dev \
    zip \
    curl \
    && docker-php-ext-install intl pdo pdo_mysql zip

# Installation des extensions composer
# cf.https://hub.docker.com/_/composer#php-version--extensions
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Définition d'un répertoire de travail : dossier qui sera créé à l'intérieur du container
# Par convention sur Linux, les projets sont mis dans le chemin absolu /var/www
WORKDIR /var/www

# Indication du chemin relatif du code source à copier <.> vers le chemin absolu dans workdir du conteneur </var/www>
COPY . /var/www

# Installation des packages Symfony pour que l'application fonctionne
RUN composer install

# Définition du port d'écoute de l'application
EXPOSE 8000

# Commande que doit exécuter le conteneur pour démarrer
# "symfony" → l'exécutable
# "server:start" → l'argument
CMD [ "symfony" ,"server:start" ]