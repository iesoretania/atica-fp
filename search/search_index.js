const local_index = {"config":{"indexing":"full","lang":["es"],"min_search_length":3,"prebuild_index":false,"separator":"[\\s\\-]+"},"docs":[{"location":"index.html","text":"Introducci\u00f3n a \u00c1TICA-FP Es una aplicaci\u00f3n web para ayudar en el seguimiento del alumnado de FP durante la Formaci\u00f3n en Centros de Trabajo y durante el per\u00edodo de alternancia de FP dual . Concretamente, intenta que la comunicaci\u00f3n entre centro educativo y las entidades colaboradoras sea \u00e1gil, eficaz e instant\u00e1neo. Esto incluye registrar las actividades realizadas fuera del centro, los contactos/visitas y reuniones de tutorizaci\u00f3n realizados por el profesorado y la valoraci\u00f3n del desempe\u00f1o de los estudiantes. El c\u00f3digo del proyecto es software libre bajo licencia AGPL versi\u00f3n 3 con lo que puede desplegarse en tu centro educativo sin necesitar ning\u00fan tipo de autorizaci\u00f3n e incluso modificarlo para adaptarlo a nuevas necesidades. Eso s\u00ed, los cambios y mejoras que se introduzcan deben estar disponibles para quien quiera incorporarlos. De esta manera, todos salimos ganando. La aplicaci\u00f3n se nutre de informaci\u00f3n que debe ser introducida en la plataforma. Siempre que ha sido posible, hemos intentado que se pueda importar informaci\u00f3n previamente exportada desde el Sistema de Informaci\u00f3n S\u00e9neca, de la Consejer\u00eda de Desarrollo Educativo y Formaci\u00f3n Profesional de la Junta de Andaluc\u00eda. Dado que la base de S\u00e9neca tambi\u00e9n se usa en otras comunidades aut\u00f3nomas (con otro nombre, eso s\u00ed) puede que el formato de exportaci\u00f3n sea compatible. Se puede seguir el desarrollo de esta herramienta v\u00eda Twitter siguiendo a @aticaFP o desde su repositorio GitHub . Este proyecto est\u00e1 desarrollado en PHP utilizando Symfony y otros muchos componentes que se instalan usando Composer y npmjs .","title":"Introducci\u00f3n"},{"location":"index.html#introduccion-a-atica-fp","text":"Es una aplicaci\u00f3n web para ayudar en el seguimiento del alumnado de FP durante la Formaci\u00f3n en Centros de Trabajo y durante el per\u00edodo de alternancia de FP dual . Concretamente, intenta que la comunicaci\u00f3n entre centro educativo y las entidades colaboradoras sea \u00e1gil, eficaz e instant\u00e1neo. Esto incluye registrar las actividades realizadas fuera del centro, los contactos/visitas y reuniones de tutorizaci\u00f3n realizados por el profesorado y la valoraci\u00f3n del desempe\u00f1o de los estudiantes. El c\u00f3digo del proyecto es software libre bajo licencia AGPL versi\u00f3n 3 con lo que puede desplegarse en tu centro educativo sin necesitar ning\u00fan tipo de autorizaci\u00f3n e incluso modificarlo para adaptarlo a nuevas necesidades. Eso s\u00ed, los cambios y mejoras que se introduzcan deben estar disponibles para quien quiera incorporarlos. De esta manera, todos salimos ganando. La aplicaci\u00f3n se nutre de informaci\u00f3n que debe ser introducida en la plataforma. Siempre que ha sido posible, hemos intentado que se pueda importar informaci\u00f3n previamente exportada desde el Sistema de Informaci\u00f3n S\u00e9neca, de la Consejer\u00eda de Desarrollo Educativo y Formaci\u00f3n Profesional de la Junta de Andaluc\u00eda. Dado que la base de S\u00e9neca tambi\u00e9n se usa en otras comunidades aut\u00f3nomas (con otro nombre, eso s\u00ed) puede que el formato de exportaci\u00f3n sea compatible. Se puede seguir el desarrollo de esta herramienta v\u00eda Twitter siguiendo a @aticaFP o desde su repositorio GitHub . Este proyecto est\u00e1 desarrollado en PHP utilizando Symfony y otros muchos componentes que se instalan usando Composer y npmjs .","title":"Introducci\u00f3n a \u00c1TICA-FP"},{"location":"SUMMARY.html","text":"Introducci\u00f3n Instalaci\u00f3n Configuraci\u00f3n inicial Manual de administraci\u00f3n Manual de usuario","title":"SUMMARY"},{"location":"configuracion/index.html","text":"Configuraci\u00f3n inicial Configuraci\u00f3n de la plataforma Configuraci\u00f3n de un centro educativo","title":"Configuraci\u00f3n inicial"},{"location":"configuracion/index.html#configuracion-inicial","text":"","title":"Configuraci\u00f3n inicial"},{"location":"configuracion/index.html#configuracion-de-la-plataforma","text":"","title":"Configuraci\u00f3n de la plataforma"},{"location":"configuracion/index.html#configuracion-de-un-centro-educativo","text":"","title":"Configuraci\u00f3n de un centro educativo"},{"location":"instalacion/index.html","text":"Instalaci\u00f3n de una instancia Consideraciones previas Instalar la plataforma en un servidor necesita de unos conocimientos m\u00ednimos de administraci\u00f3n de sistemas. Es recomendable contar con personal que tenga experiencia tanto en este tema como aspectos de seguridad inform\u00e1tica.","title":"Instalaci\u00f3n"},{"location":"instalacion/index.html#instalacion-de-una-instancia","text":"","title":"Instalaci\u00f3n de una instancia"},{"location":"instalacion/index.html#consideraciones-previas","text":"Instalar la plataforma en un servidor necesita de unos conocimientos m\u00ednimos de administraci\u00f3n de sistemas. Es recomendable contar con personal que tenga experiencia tanto en este tema como aspectos de seguridad inform\u00e1tica.","title":"Consideraciones previas"},{"location":"instalacion/pasos.html","text":"Pasos de la instalaci\u00f3n Clonar el repositorio Ejecutar composer install desde la carpeta del proyecto. Hacer una copia del fichero .env en .env.local Modifica la configuraci\u00f3n cambiando el contenido de .env.local Ejecutar npm install Ejecutar el comando node_modules/.bin/encore prod para generar los assets. Configurar el sitio de Apache2 para que el DocumentRoot sea la carpeta public/ dentro de la carpeta de instalaci\u00f3n. Activar en Apache2 mod_rewrite (en S.O. Linux prueba con el comando a2enmod rewrite y reiniciando el servicio) Si a\u00fan no se ha hecho, modificar el fichero .env.local con los datos de acceso al sistema gestor de bases de datos deseados y otros par\u00e1metros de configuraci\u00f3n globales que considere interesantes. Para crear la base de datos: php bin/console doctrine:database:create Para crear las tablas: php bin/console doctrine:schema:create php bin/console doctrine:migrations:version --add --all Para insertar los datos iniciales: (con la base de datos vac\u00eda) bin/console app:organization \"I.E.S. Test\" --code=23999999 --city=Linares (cambia los datos seg\u00fan tu centro) bin/console app:admin admin --firstname=Admin --lastname=ATICA --password=admin Esto crear\u00e1 un usuario admin con contrase\u00f1a admin . Habr\u00e1 que cambiarla la primera vez que se acceda.","title":"Pasos de la instalaci\u00f3n"},{"location":"instalacion/pasos.html#pasos-de-la-instalacion","text":"Clonar el repositorio Ejecutar composer install desde la carpeta del proyecto. Hacer una copia del fichero .env en .env.local Modifica la configuraci\u00f3n cambiando el contenido de .env.local Ejecutar npm install Ejecutar el comando node_modules/.bin/encore prod para generar los assets. Configurar el sitio de Apache2 para que el DocumentRoot sea la carpeta public/ dentro de la carpeta de instalaci\u00f3n. Activar en Apache2 mod_rewrite (en S.O. Linux prueba con el comando a2enmod rewrite y reiniciando el servicio) Si a\u00fan no se ha hecho, modificar el fichero .env.local con los datos de acceso al sistema gestor de bases de datos deseados y otros par\u00e1metros de configuraci\u00f3n globales que considere interesantes. Para crear la base de datos: php bin/console doctrine:database:create Para crear las tablas: php bin/console doctrine:schema:create php bin/console doctrine:migrations:version --add --all Para insertar los datos iniciales: (con la base de datos vac\u00eda) bin/console app:organization \"I.E.S. Test\" --code=23999999 --city=Linares (cambia los datos seg\u00fan tu centro) bin/console app:admin admin --firstname=Admin --lastname=ATICA --password=admin Esto crear\u00e1 un usuario admin con contrase\u00f1a admin . Habr\u00e1 que cambiarla la primera vez que se acceda.","title":"Pasos de la instalaci\u00f3n"},{"location":"instalacion/pruebas.html","text":"Prueba de la aplicaci\u00f3n Prueba r\u00e1pida mediante Docker Compose ATENCI\u00d3N: No se recomienda ejecutarlo as\u00ed en entornos de producci\u00f3n, tan s\u00f3lo se sugiere para pruebas internas. Ejecutar docker-compose up -d desde la carpeta del proyecto El usuario ser\u00e1 admin y la contrase\u00f1a admin . Habr\u00e1 que cambiarla en la primera entrada. \u00bfQuieres cargar unos datos de prueba en vez de que est\u00e9 vac\u00edo? Si usas Linux, con el comando DEMO=1 docker-compose up -d Si usas Windows, abre un PowerShell y ejecuta $env:DEMO=1; docker-compose up -d En estos caso, el usuario ser\u00e1 admin y la contrase\u00f1a aticafp Esperar... Acceder desde el navegador a la direcci\u00f3n http://127.0.0.1:9999 Si usas Docker Toolbox usa esta direcci\u00f3n en su lugar: http://192.168.99.100:9999 \u00a1Listo! NOTA: La carpeta data contendr\u00e1 la base de datos, puedes sacar copias de seguridad de la misma si lo estimas conveniente.","title":"Prueba de la aplicaci\u00f3n"},{"location":"instalacion/pruebas.html#prueba-de-la-aplicacion","text":"","title":"Prueba de la aplicaci\u00f3n"},{"location":"instalacion/pruebas.html#prueba-rapida-mediante-docker-compose","text":"ATENCI\u00d3N: No se recomienda ejecutarlo as\u00ed en entornos de producci\u00f3n, tan s\u00f3lo se sugiere para pruebas internas. Ejecutar docker-compose up -d desde la carpeta del proyecto El usuario ser\u00e1 admin y la contrase\u00f1a admin . Habr\u00e1 que cambiarla en la primera entrada. \u00bfQuieres cargar unos datos de prueba en vez de que est\u00e9 vac\u00edo? Si usas Linux, con el comando DEMO=1 docker-compose up -d Si usas Windows, abre un PowerShell y ejecuta $env:DEMO=1; docker-compose up -d En estos caso, el usuario ser\u00e1 admin y la contrase\u00f1a aticafp Esperar... Acceder desde el navegador a la direcci\u00f3n http://127.0.0.1:9999 Si usas Docker Toolbox usa esta direcci\u00f3n en su lugar: http://192.168.99.100:9999 \u00a1Listo! NOTA: La carpeta data contendr\u00e1 la base de datos, puedes sacar copias de seguridad de la misma si lo estimas conveniente.","title":"Prueba r\u00e1pida mediante Docker Compose"},{"location":"instalacion/requisitos.html","text":"Requisitos PHP 7.2.24 o superior. Servidor web Apache2 (podr\u00eda funcionar con nginx, pero no se ha probado a\u00fan). Servidor de base de datos MySQL 5.7 o superior, o bien alg\u00fan derivado (como MariaDB, Percona, etc). Atenci\u00f3n: Con MySQL 8.0.20 o superior, es necesario aumentar el par\u00e1metro sort_buffer_size en la secci\u00f3n [mysqld] . Con 1M parece funcionar bien. PHP Composer . Node.js \u226512.","title":"Requisitos"},{"location":"instalacion/requisitos.html#requisitos","text":"PHP 7.2.24 o superior. Servidor web Apache2 (podr\u00eda funcionar con nginx, pero no se ha probado a\u00fan). Servidor de base de datos MySQL 5.7 o superior, o bien alg\u00fan derivado (como MariaDB, Percona, etc). Atenci\u00f3n: Con MySQL 8.0.20 o superior, es necesario aumentar el par\u00e1metro sort_buffer_size en la secci\u00f3n [mysqld] . Con 1M parece funcionar bien. PHP Composer . Node.js \u226512.","title":"Requisitos"},{"location":"manual_admin/index.html","text":"Manual de administraci\u00f3n","title":"Manual de administraci\u00f3n"},{"location":"manual_admin/index.html#manual-de-administracion","text":"","title":"Manual de administraci\u00f3n"},{"location":"manual_usuario/index.html","text":"Manual de usuario","title":"Manual de usuario"},{"location":"manual_usuario/index.html#manual-de-usuario","text":"","title":"Manual de usuario"}]}; var __search = { index: Promise.resolve(local_index) }