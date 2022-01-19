ATICA-FP
========

Aplicación web para ayudar en el seguimiento del alumnado de FP durante la Formación en Centros de Trabajo y
durante el período de alternancia de FP dual.

Puedes seguir el desarrollo de esta herramienta vía Twitter siguiendo a [@aticaFP].

Este proyecto está desarrollado en PHP utilizando [Symfony] y otros muchos componentes que se instalan usando
[Composer] y [npmjs].

## Requisitos

- PHP 7.2.24 o superior.
- Servidor web Apache2 (podría funcionar con nginx, pero no se ha probado aún).
- Servidor de base de datos MySQL 5.7 o superior, o bien algún derivado (como MariaDB, Percona, etc).
- PHP [Composer].
- [Node.js] ≥12.

## Prueba rápida mediante Docker Compose

**ATENCIÓN: No se recomienda ejecutarlo así en entornos de producción, tan sólo se sugiere para pruebas internas.**
- Ejecutar `docker-compose up -d` desde la carpeta del proyecto
- Esperar...
- Acceder desde el navegador a la dirección http://127.0.0.1:9999
  * Si usas Docker Toolbox usa esta dirección en su lugar: http://192.168.99.100:9999
- ¡Listo!


## Instalación

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
- Para insertar los datos iniciales: `php bin/console doctrine:fixtures:load -n` (¡cuidado! Esto elimina todos los datos existentes en la base de datos).

## Configuración

- Entrar en la plataforma con el nombre de usuario `admin` y la contraseña `admin`
- Será necesario cambiar la contraseña por defecto tras el primer acceso

## Actualizaciones

- Actualizar el repositorio a la última versión oficial.
- Actualizar la base de datos:
  - `php bin/console doctrine:migrations:migrate -n`
- Ejecutar `composer install` desde la carpeta del proyecto.
- Ejecutar `npm install`
- Ejecutar el comando `node_modules/.bin/encore prod` para generar los assets.

## Licencia
Esta aplicación se ofrece bajo licencia [AGPL versión 3].

[Symfony]: http://symfony.com/
[Composer]: http://getcomposer.org
[AGPL versión 3]: http://www.gnu.org/licenses/agpl.html
[Node.js]: https://nodejs.org/en/
[@aticaFP]: https://twitter.com/aticaFP
