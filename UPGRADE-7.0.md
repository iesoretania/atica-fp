UPGRADE 7.0
===========

**Atención: Se recomienda sacar copia de seguridad de la base de datos antes de migrar
a la nueva versión, no es posible revertir la migración a versiones anteriores.**

Este documento contiene una lista de los cambios más relevantes entre la versión 6.x.x
de la plataforma y la 7.0.

Actualización de los componentes internos
-----------------------------------------
Para soportar las últimas versiones de algunos componentes, se han tenido que modificar los requisitos
mínimos de PHP y NodeJS. Ahora se requiere PHP ≥ 8.2 y NodeJS ≥ 20.

Soporte de la fase de formación en empresa
------------------------------------------
La aplicación incluye un nuevo módulo para gestionar la fase de formación en empresa de los estudiantes
atendiendo a la nueva normativa de FP.

Copias de seguridad desde la consola
------------------------------------
Ahora es posible generar y recuperar copias de seguridad desde la consola de comandos. Los comandos son:
- ```bin/console app:backup``` para generar una copia de seguridad. Parámetros:
  * ```--path <directorio>``` (opcional): Permite indicar el directorio donde se guardará la copia de seguridad. Por defecto será la carpeta ```/backups``` del despliegue.
  * ```--filename <nombre_fichero>``` (opcional): Establecer el nombre del fichero con que se guardará la copia de seguridad. Por defecto será ```backup.sql```.
  * ```--timestamp``` (opcional): Si se indica, se añadirá la fecha y hora al nombre del archivo de copia de seguridad. Es incompatible con la opción ```--filename```.
- ```bin/console app:backup-restore``` para recuperar una copia de seguridad. Parámetros:
    * ```--path <directorio>``` (opcional): Permite indicar el directorio donde se encuentra la copia de seguridad. Por defecto será la carpeta ```/backups``` del despliegue.
    * ```--filename <nombre_fichero>``` (opcional): Establecer el nombre del fichero desde el que se restaurará la copia de seguridad. Por defecto será ```backup.sql```.

Migraciones seguras
-------------------
Para evitar pérdidas de datos potenciales al realizar una migración, se puede realizar una migración segura desde la consola:
- ```bin/console app:safe-migrate``` para realizarla.

Los parámetros son los mismos que con la migración habitual, incluyendo ```-n``` para realizarla sin pedir confirmación.
