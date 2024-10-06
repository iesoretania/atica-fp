ATICA-FP
========

Aplicación web para ayudar en el seguimiento del alumnado de FP durante la Formación en Centros de Trabajo y
durante el período de alternancia de FP dual.

Puedes seguir el desarrollo de esta herramienta vía X (antes Twitter) siguiendo a [@aticaFP].

Este proyecto está desarrollado en PHP utilizando [Symfony] 6.4 y otros muchos componentes que se instalan usando
[Composer] y [npmjs].

## Requisitos

- PHP 8.2 o superior.
- Servidor web Apache2 (podría funcionar con nginx, pero no se ha probado aún).
- Servidor de base de datos MySQL 8.0.28 o superior, o bien algún derivado equivalente (como MariaDB 11, Percona, etc).
- PHP [Composer].
- [Node.js] ≥ 20.

## Prueba rápida mediante Docker Compose

**ATENCIÓN: No se recomienda ejecutarlo así en entornos de producción, únicamente se sugiere para pruebas internas.**
- Ejecutar `docker-compose up -d` desde la carpeta del proyecto
  * El usuario será `admin` y la contraseña `admin`. Habrá que cambiarla en la primera entrada.
  * ¿Quieres cargar unos datos de prueba en vez de que esté vacío?
    * Si usas Linux, con el comando `DEMO=1 docker-compose up -d`
    * Si usas Windows, abre un PowerShell y ejecuta `$env:DEMO=1;  docker-compose up -d`
    * En estos caso, el usuario será `admin` y la contraseña `aticafp`
- Esperar... (bastante tiempo)
- Acceder desde el navegador a la dirección http://127.0.0.1:9999
  * Si usas Docker Toolbox usa esta dirección en su lugar: http://192.168.99.100:9999
- ¡Listo!

**NOTA: La carpeta `data` contendrá la base de datos, puedes sacar copias de seguridad de la misma si lo estimas conveniente.**

## Instalación en un servidor

- Clonar el repositorio en la carpeta deseada.
- Ejecutar `composer install` desde la carpeta del proyecto.
- Hacer una copia del fichero `.env.local.sample` en `.env.local`
  - Modifica la configuración cambiando el contenido de `.env.local`
  - Es importante cambiar la variable `APP_SECRET` por un valor aleatorio, no dejes el que aparece.
  - Cambia la variable `DATABASE_URL` con los datos de acceso a la base de datos.
- Ejecutar `npm install`
- Ejecutar el comando `npm run build` para generar los assets.
- Configurar el sitio de Apache2 para que el `DocumentRoot` sea la carpeta `public/` dentro de la carpeta de instalación.
- Activar en Apache2 `mod_rewrite` (en S.O. Linux prueba con el comando `a2enmod rewrite` y reiniciando el servicio)
- Para crear la base de datos: `php bin/console doctrine:database:create`
- Para crear las tablas:
  - `php bin/console doctrine:schema:create`
  - `php bin/console doctrine:migrations:version --add --all`
- Para insertar los datos iniciales: (con la base de datos vacía)
  - `bin/console app:organization "I.E.S. Test" --code=23999999 --city=Linares` (cambia los datos según tu centro)
  - `bin/console app:admin admin --firstname=Admin --lastname=ATICA --password=admin`
  - Esto creará un usuario `admin` con contraseña `admin`. Habrá que cambiarla la primera vez que se acceda.

## Primer acceso

- Entrar en la plataforma con el nombre de usuario `admin` y la contraseña `admin`
- Será necesario cambiar la contraseña por defecto tras el primer acceso

## Actualizaciones

Cuando se publique una nueva versión de la plataforma, se deberán seguir los siguientes pasos para actualizar:

- Incorporar al repositorio los cambios de la última versión oficial (```git pull```).
- Ejecutar `composer install` desde la carpeta del proyecto.
- Ejecutar `npm install`
- Actualizar la base de datos (se sacará una copia de seguridad automáticamente):
  - `php bin/console app:safe-migrate -n`
    **IMPORTANTE:** Si fallara el comando anterior por la ausencia de algún comando en el sistema, se puede intentar
    con `php bin/console doctrine:migrations:migrate -n`, que realiza la migración sin copia de seguridad previa.
- Ejecutar el comando `npm run build` para generar los assets.

## Licencia
Esta aplicación se ofrece bajo licencia [AGPL versión 3].

[Symfony]: http://symfony.com/
[Composer]: http://getcomposer.org
[AGPL versión 3]: http://www.gnu.org/licenses/agpl.html
[Node.js]: https://nodejs.org/en/
[npmjs]: https://npmjs.com/
[@aticaFP]: https://twitter.com/aticaFP
