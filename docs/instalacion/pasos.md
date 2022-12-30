# Pasos de la instalación

- Clonar el repositorio
- Ejecutar `composer install` desde la carpeta del proyecto.
- Hacer una copia del fichero `.env` en `.env.local`
    - Modifica la configuración cambiando el contenido de `.env.local`
- Ejecutar `npm install`
- Ejecutar el comando `node_modules/.bin/encore prod` para generar los assets.
- Configurar el sitio de Apache2 para que el `DocumentRoot` sea la carpeta `public/` dentro de la carpeta de instalación.
- Activar en Apache2 `mod_rewrite` (en S.O. Linux prueba con el comando `a2enmod rewrite` y reiniciando el servicio)
- Si aún no se ha hecho, modificar el fichero `.env.local` con los datos de acceso al sistema gestor de bases de datos deseados y otros parámetros de configuración globales que considere interesantes.
- Para crear la base de datos: `php bin/console doctrine:database:create`
- Para crear las tablas:
    - `php bin/console doctrine:schema:create`
    - `php bin/console doctrine:migrations:version --add --all`
- Para insertar los datos iniciales: (con la base de datos vacía)
    - `bin/console app:organization "I.E.S. Test" --code=23999999 --city=Linares` (cambia los datos según tu centro)
    - `bin/console app:admin admin --firstname=Admin --lastname=ATICA --password=admin`
    - Esto creará un usuario `admin` con contraseña `admin`. Habrá que cambiarla la primera vez que se acceda.
