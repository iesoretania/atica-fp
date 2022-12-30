const local_index = {"config":{"indexing":"full","lang":["es"],"min_search_length":3,"prebuild_index":false,"separator":"[\\s\\-]+"},"docs":[{"location":"index.html","text":"ATICA-FP Aplicaci\u00f3n web para ayudar en el seguimiento del alumnado de FP durante la Formaci\u00f3n en Centros de Trabajo y durante el per\u00edodo de alternancia de FP dual. Puedes seguir el desarrollo de esta herramienta v\u00eda Twitter siguiendo a @aticaFP . Este proyecto est\u00e1 desarrollado en PHP utilizando Symfony y otros muchos componentes que se instalan usando Composer y npmjs .","title":"Introducci\u00f3n"},{"location":"index.html#atica-fp","text":"Aplicaci\u00f3n web para ayudar en el seguimiento del alumnado de FP durante la Formaci\u00f3n en Centros de Trabajo y durante el per\u00edodo de alternancia de FP dual. Puedes seguir el desarrollo de esta herramienta v\u00eda Twitter siguiendo a @aticaFP . Este proyecto est\u00e1 desarrollado en PHP utilizando Symfony y otros muchos componentes que se instalan usando Composer y npmjs .","title":"ATICA-FP"},{"location":"SUMMARY.html","text":"Introducci\u00f3n Requisitos Instalaci\u00f3n","title":"SUMMARY"},{"location":"instalacion.html","text":"Instalaci\u00f3n Ejecutar composer install desde la carpeta del proyecto. Hacer una copia del fichero .env en .env.local Modifica la configuraci\u00f3n cambiando el contenido de .env.local Ejecutar npm install Ejecutar el comando node_modules/.bin/encore prod para generar los assets. Configurar el sitio de Apache2 para que el DocumentRoot sea la carpeta public/ dentro de la carpeta de instalaci\u00f3n. Activar en Apache2 mod_rewrite (en S.O. Linux prueba con el comando a2enmod rewrite y reiniciando el servicio) Si a\u00fan no se ha hecho, modificar el fichero .env.local con los datos de acceso al sistema gestor de bases de datos deseados y otros par\u00e1metros de configuraci\u00f3n globales que considere interesantes. Para crear la base de datos: php bin/console doctrine:database:create Para crear las tablas: php bin/console doctrine:schema:create php bin/console doctrine:migrations:version --add --all Para insertar los datos iniciales: (con la base de datos vac\u00eda) bin/console app:organization \"I.E.S. Test\" --code=23999999 --city=Linares (cambia los datos seg\u00fan tu centro) bin/console app:admin admin --firstname=Admin --lastname=ATICA --password=admin Esto crear\u00e1 un usuario admin con contrase\u00f1a admin . Habr\u00e1 que cambiarla la primera vez que se acceda.","title":"Instalaci\u00f3n"},{"location":"instalacion.html#instalacion","text":"Ejecutar composer install desde la carpeta del proyecto. Hacer una copia del fichero .env en .env.local Modifica la configuraci\u00f3n cambiando el contenido de .env.local Ejecutar npm install Ejecutar el comando node_modules/.bin/encore prod para generar los assets. Configurar el sitio de Apache2 para que el DocumentRoot sea la carpeta public/ dentro de la carpeta de instalaci\u00f3n. Activar en Apache2 mod_rewrite (en S.O. Linux prueba con el comando a2enmod rewrite y reiniciando el servicio) Si a\u00fan no se ha hecho, modificar el fichero .env.local con los datos de acceso al sistema gestor de bases de datos deseados y otros par\u00e1metros de configuraci\u00f3n globales que considere interesantes. Para crear la base de datos: php bin/console doctrine:database:create Para crear las tablas: php bin/console doctrine:schema:create php bin/console doctrine:migrations:version --add --all Para insertar los datos iniciales: (con la base de datos vac\u00eda) bin/console app:organization \"I.E.S. Test\" --code=23999999 --city=Linares (cambia los datos seg\u00fan tu centro) bin/console app:admin admin --firstname=Admin --lastname=ATICA --password=admin Esto crear\u00e1 un usuario admin con contrase\u00f1a admin . Habr\u00e1 que cambiarla la primera vez que se acceda.","title":"Instalaci\u00f3n"},{"location":"requisitos.html","text":"Requisitos PHP 7.2.24 o superior. Servidor web Apache2 (podr\u00eda funcionar con nginx, pero no se ha probado a\u00fan). Servidor de base de datos MySQL 5.7 o superior, o bien alg\u00fan derivado (como MariaDB, Percona, etc). Atenci\u00f3n: Con MySQL 8.0.20 o superior, es necesario aumentar el par\u00e1metro sort_buffer_size en la secci\u00f3n [mysqld] . Con 1M parece funcionar bien. PHP Composer . Node.js \u226512.","title":"Requisitos"},{"location":"requisitos.html#requisitos","text":"PHP 7.2.24 o superior. Servidor web Apache2 (podr\u00eda funcionar con nginx, pero no se ha probado a\u00fan). Servidor de base de datos MySQL 5.7 o superior, o bien alg\u00fan derivado (como MariaDB, Percona, etc). Atenci\u00f3n: Con MySQL 8.0.20 o superior, es necesario aumentar el par\u00e1metro sort_buffer_size en la secci\u00f3n [mysqld] . Con 1M parece funcionar bien. PHP Composer . Node.js \u226512.","title":"Requisitos"}]}; var __search = { index: Promise.resolve(local_index) }