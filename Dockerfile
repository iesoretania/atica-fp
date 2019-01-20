FROM ubuntu:16.04

# Instalar Apache2+PHP y el resto de dependencias
ENV DEBIAN_FRONTEND noninteractive
RUN apt-get update \
    && apt-get -yq install \
        sudo \
        bsdmainutils \
        curl \
        libapache2-mod-php \
        php-intl \
        php-curl \
        php-mbstring \
        php-xml \
        php-zip \
        php-pdo-mysql \
        mysql-client \
        zip \
    && rm -rf /var/lib/apt/lists/*

# Descargar composer
RUN curl --insecure https://getcomposer.org/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer

# Añadir configuración de zona horaria a PHP
ADD ./docker/symfony.ini /etc/php/7.0/apache2/conf.d/
ADD ./docker/symfony.ini /etc/php/7.0/cli/conf.d/

# Activar mod_rewrite para URL amigables
RUN a2enmod rewrite

# Copiar la aplicación
COPY . /var/www/symfony/

# Copiar el script de inicialización
ADD ./docker/run.sh /run.sh

# Añadir permisos de ejecución al script y
# cambiar configuración de apache2 para apuntar
# al nuevo DocumentRoot y aceptar configuración
# por .htaccess
RUN chmod 0755 /run.sh \
    && sed -i 's!/var/www/html!/var/www/symfony/web!g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

# Copiar parámetros de la aplicación Symfony
COPY ./docker/parameters.yml /var/www/symfony/app/config/

# Asegurar los permisos correctos en la aplicación
RUN chown -R www-data:www-data /var/www/symfony

# Directorio de trabajo, el de la aplicación
WORKDIR /var/www/symfony

# Exponer el puerto 80
EXPOSE 80

# Indicar el script de arranque del contenedor
CMD ["/run.sh"]
