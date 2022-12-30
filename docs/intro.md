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
    * Atención: Con MySQL 8.0.20 o superior, es necesario aumentar el parámetro `sort_buffer_size` en
      la sección `[mysqld]`. Con 1M parece funcionar bien.
- PHP [Composer].
- [Node.js] ≥12.

[Symfony]: http://symfony.com/
[Composer]: http://getcomposer.org
[AGPL versión 3]: http://www.gnu.org/licenses/agpl.html
[Node.js]: https://nodejs.org/en/
[npmjs]: https://npmjs.com/
[@aticaFP]: https://twitter.com/aticaFP
