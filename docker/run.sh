#!/bin/bash

# Este script se ejecuta cada vez que arranca el
# contenedor

# Corregir permisos
mkdir -p var/log
mkdir -p var/cache/prod
touch var/log/prod.log
chmod -R g+w var/log
chown -R www-data:www-data var

# Cargar variables de entorno de Apache2
source /etc/apache2/envvars

# Instalar componentes de composer
sudo -u www-data composer install

# Instalar componentes de npm
sudo npm install -g npm
sudo -u www-data npm install

# Compilar assets de CSS y JS
sudo -u www-data node_modules/.bin/encore prod

# Ejecutar migración
sudo -u www-data php bin/console --no-interaction d:m:m

# Comprobar si hay usuarios en la base de datos
RESULT=`MYSQL_PWD=atica mysql -h db --user=atica aticafp -N -s -r -e "SELECT COUNT(*) FROM person"`
if [ "$RESULT" == "0" ]; then
    # Si no es así, generar un secreto nuevo
    SECRET="`hexdump -n 16 -e '4/4 "%08X" 1 "\n"' /dev/random`" && sudo -u www-data sed -i -e "s/APP_SECRET=:.*/APP_SECRET=$SECRET/" /var/www/symfony/.env.local
    if [ "$DEMO" == "1" ]; then
        # Datos de demostración
        echo "Incorporando datos de prueba..."
        MYSQL_PWD=atica mysql -h db --user=atica aticafp < /demo.sql
    else
        # Crear un centro inicial y un usuario "admin" con contraseña "admin"
        sudo -u www-data php bin/console app:organization "I.E.S. Test" --code=23999999 --city=Linares
        sudo -u www-data php bin/console app:admin admin --firstname=Admin --lastname=ATICA --password=admin
    fi
fi

# Arrancar Apache2
tail -F /var/log/apache2/* var/log/prod.log &
exec apache2 -D FOREGROUND
