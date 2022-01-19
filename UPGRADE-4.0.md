UPGRADE 4.0
===========

**Atención: Se recomienda sacar copia de seguridad de la base de datos antes de migrar
a la nueva versión. La tabla encargada de gestionar los cambios de la base de datos se
actualizará durante el proceso y no podrá ser correctamente interpretada en las versiones
anteriores de la aplicación.**

Este documento contiene una lista de los cambios más relevantes entre la versión 3.x.x
de la plataforma y la 4.0.

Lea con atención porque algunos de ellos implican la imposibilidad de volver a versiones anteriores
manteniendo la base de datos.

Nuevos requisitos mínimos
-------------------------
Ahora es necesario tener instalado PHP 7.2 o superior, así como NodeJS 12 o una versión
más reciente para poder generar los estilos y scripts necesarios.

Cambios en la configuración
---------------------------
Si se actualiza una versión 3.x o anterior, hay que copiar el fichero `.env` en otro fichero
llamado `.env.local` y copiar manualmente los parámetros de configuración que se encontraban
en `app\config\parameters.yml`. De otra forma, no funcionará la aplicación.

El formato de configuración de la conexión a la base de datos es, en ese fichero, así:
> DATABASE_URL="mysql://`usuario`:`contraseña`@`host`:3306/`nombre_base_de_datos`?serverVersion=5.7"

La configuración del envío de correo electrónico usando GMail:
> MAILER_URL=gmail://`usuario@gmail.com`:`contraseña`@localhost

Formato de la tabla de migraciones actualizado
----------------------------------------------
Hay un cambio interno en la tabla que se encarga de controlar qué actualizaciones de la base
de datos han sido instaladas y cuáles están pendientes. Eso implica que no se podrá volver
a versiones anteriores de la aplicación manteniendo la base de datos sin cambios.

Actualizado el estilo de la aplicación
--------------------------------------
Se ha cambiado el aspecto de la interfaz de usuario con el fin de hacerla más cómoda de usar
en dispositivos con un tamaño de pantalla limitado, como es el caso de los dispositivos móviles.

