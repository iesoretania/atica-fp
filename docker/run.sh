#!/bin/bash

# Este script se ejecuta cada vez que arranca el
# contenedor

# Corregir permisos
mkdir -p var/log
touch var/log/prod.log
chgrp -R www-data .
chmod -R g+w var/log

# Cargar variables de entorno de Apache2
source /etc/apache2/envvars

# Instalar componentes de composer
sudo -u www-data composer install

# Instalar componentes de npm
sudo -u www-data npm install

# Compilar assets de CSS y JS
sudo -u www-data node_modules/.bin/encore prod

# Ejecutar migración
sudo -u www-data php bin/console --no-interaction d:m:m

# Comprobar si hay usuarios en la base de datos
# Si no es así, generar un secreto nuevo y lanzar fixtures
RESULT=`MYSQL_PWD=atica mysql -h db --user=atica aticafp -N -s -r -e "SELECT COUNT(*) FROM user"`
if [ "$RESULT" == "0" ]; then
   SECRET="`hexdump -n 16 -e '4/4 "%08X" 1 "\n"' /dev/random`" && sudo -u www-data sed -i -e "s/APP_SECRET=:.*/APP_SECRET=$SECRET/" /var/www/symfony/.env.local
   sudo -u www-data php bin/console d:f:l -n
fi

# Arrancar Apache2
tail -F /var/log/apache2/* var/log/prod.log &
exec apache2 -D FOREGROUND
