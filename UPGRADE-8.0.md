UPGRADE 8.0
===========

**Atención: Se recomienda sacar copia de seguridad de la base de datos antes de migrar
a la nueva versión.**

Este documento contiene una lista de los cambios más relevantes entre la versión 7.x.x
de la plataforma y la 8.0.

Nueva política de ramas en el repositorio
-----------------------------------------
Igual que en la versión anterior, la rama ```v8``` contendrá la versión 8 de la plataforma y,
permaneciendo en ella, no se modificarán los requisitos mínimos de PHP, etc.

La rama ```v7``` pasa a modo de mantenimiento: seguirá existiendo, pero sin recibir nuevas
características ni actualizaciones de seguridad salvo que sean solicitadas por algún centro educativo.

Nuevo diseño de la interfaz
---------------------------
Toda la aplicación estrena una interfaz nueva: menú lateral, escritorio con saludo y resumen del
seguimiento de cada módulo (FFEOE, FCT y FP dual), tarjetas de menú con descripción, migas de pan más
cortas, pantalla de acceso y pantallas de error rediseñadas, listados y formularios dinámicos y nuevo icono.
Se han retirado Bootstrap, jQuery, Select2 y Font Awesome. El funcionamiento y los permisos no cambian.

Si se usa un logo propio (variables ```APP_LOGO``` y ```APP_LOGO_NAVBAR``` del fichero ```.env.local```),
conviene revisar cómo se ve: ahora se muestra sobre un fondo oscuro en la pantalla de acceso y en la barra lateral.

Ya no se necesita Node.js
-------------------------
Los ficheros de la interfaz (CSS y JavaScript) ya no se generan con Webpack Encore, sino con el componente
AssetMapper de Symfony y Tailwind CSS, de modo que **ya no hace falta instalar Node.js** ni ejecutar
```npm install``` ni ```npm run build```. Las librerías de JavaScript se descargan durante ```composer install```
y el binario de Tailwind CSS se descarga automáticamente la primera vez que se publican los assets
(se necesita conexión a Internet en ese momento).

Después de actualizar el código, se debe ejecutar:
```bash
composer install
php bin/console asset-map:compile
```
El editor de texto enriquecido pasa de CKEditor a Quill.

Actualización de los componentes internos
-----------------------------------------
Se actualiza Symfony a la versión 7.4. Los requisitos mínimos de PHP no cambian (PHP ≥ 8.2).
No hay migraciones de la base de datos nuevas en esta versión.
