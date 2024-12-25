UPGRADE 7.0
===========

**Atención: Se recomienda sacar copia de seguridad de la base de datos antes de migrar
a la nueva versión, no es posible revertir la migración a versiones anteriores.**

Este documento contiene una lista de los cambios más relevantes entre la versión 6.x.x
de la plataforma y la 7.0.

Nueva política de ramas en el repositorio
-----------------------------------------
A partir de esta versión, se creará una rama específica para cada versión de la plataforma.
En concreto, la rama ```v7``` contendrá la versión 7 de la plataforma y, permaneciendo en ella,
no se modificarán los requisitos mínimos de PHP, NodeJS, etc.

La versión anterior, ```v6```, seguirá existiendo pero en modo de mantenimiento, sin recibir
nuevas características ni actualizaciones de seguridad salvo que sean solicitadas por algún
centro educativo.

La rama ```master``` dejará de ser la rama principal de desarrollo y se eliminará en un futuro.
Dado que su funcionalidad se corresponde con la versión 6 de la plataforma, se recomienda que
cambiar la rama de trabajo a ```v6``` si se está en ella y no se desea actualizar a la versión 7.

Actualización de los componentes internos
-----------------------------------------
Para soportar las últimas versiones de algunos componentes, se han tenido que modificar los requisitos
mínimos de PHP y NodeJS. Ahora se requiere PHP ≥ 8.2 y NodeJS ≥ 20.

Cambios en la configuración de envío de correos electrónicos
------------------------------------------------------------
Ahora se usa otro componente más moderno para enviar correos electrónicos. Si se está usando
esta característica, se deberá modificar el fichero ```.env.local``` (o el que corresponda) ligeramente:
```.dotenv
# Antes: MAILER_URL=smtp://localhost
MAILER_DSN=smtp://localhost
```
```.dotenv
# Si se usa GMail o Google Workspace:
# Antes: MAILER_URL=gmail://direccion_de_correo_completa:contraseña@localhost
MAILER_DSN=gmail://direccion_de_correo_completa:contraseña@default
```
Por tanto, habría que cambiar la variable ```MAILER_URL``` por ```MAILER_DSN``` y, en el caso
de usar GMail o Google Workspace, cambiar ```localhost``` por ```default```.

Soporte de la fase de formación en empresa
------------------------------------------
La aplicación incluye un nuevo módulo para gestionar la fase de formación en empresa de los estudiantes
atendiendo a la nueva normativa de FP.

El funcionamiento es similar al de los proyectos de FP dual:
- Se crea un plan de formación inicial, especificando los niveles de los ciclos formativos asociados
  al mismo.
- Para cada nivel (1º, 2º, etc) se crean las actividades formativas y los criterios de evaluación
  de los módulos profesionales a los que estarán asociadas.
- Se asocian empresas a los niveles, indicando qué actividad o actividades se pueden realizar en ellas.
- Se crean planes de formación individuales, indicando el estudiante, la empresa, las actividades
  formativas que se realizarán y un calendario de realización.

Como novedades, el nuevo módulo de formación en empresa permite:
- Especificar un número no entero de horas para las jornadas del calendario.
- Crear planes de formación individuales con calendarios que incluyen sábado y domingo en la hoja de
  seguimiento semanal.

Escalas de valoracion
---------------------
Las escalas de valoración del desempeño que se configuraban específicamente para los proyectos de FP dual
ahora se especifican globalmente a nivel de centro educativo. Esto permite reutilizar las escalas de valoración
entre diferentes proyectos de FP dual y planes de formación de la nueva fase de formación en empresa.

Con este fin, se ha añadido una nueva sección en la configuración del centro educativo para gestionar las escalas.

Las escalas de valoración individuales de los proyectos de FP dual se han migrado a la nueva sección de escalas
de forma automática, asignándoles como descripción el nombre del proyecto.

Copias de seguridad desde la consola
------------------------------------
Ahora es posible generar y recuperar copias de seguridad desde la consola de comandos. Los comandos son:
- ```bin/console app:backup``` para generar una copia de seguridad. Parámetros:
  * ```<nombre_del_fichero>``` (opcional): Permite establecer el nombre del fichero con que se guardará la copia de seguridad. Si se especifica, no pueden usarse opciones.
  * ```--path <directorio>``` (opcional): Permite indicar el directorio donde se guardará la copia de seguridad. Por defecto será la carpeta ```/backups``` del despliegue.
  * ```--filename <nombre_fichero>``` (opcional): Establecer el nombre del fichero con que se guardará la copia de seguridad. Por defecto será ```backup.sql```.
  * ```--timestamp``` (opcional): Si se indica, se añadirá la fecha y hora al nombre del archivo de copia de seguridad. Es incompatible con la opción ```--filename```.
- ```bin/console app:backup-restore``` para recuperar una copia de seguridad. Parámetros:
  * ```<nombre_del_fichero>``` (opcional): Permite establecer el nombre del fichero desde el que se recuperará la copia de seguridad. Si se especifica, no pueden usarse opciones.
  * ```--path <directorio>``` (opcional): Permite indicar el directorio donde se encuentra la copia de seguridad. Por defecto será la carpeta ```/backups``` del despliegue.
  * ```--filename <nombre_fichero>``` (opcional): Establecer el nombre del fichero desde el que se restaurará la copia de seguridad. Por defecto será ```backup.sql```.
  * ```--recreate-database```: Elimina la base de datos y la vuelve a crear al recuperar la copia de seguridad.

Migraciones seguras
-------------------
Para evitar pérdidas de datos potenciales al realizar una migración, se puede realizar una migración segura desde la consola:
- ```bin/console app:safe-migrate``` para realizarla. Parámetros opcionales:
  * ```-n```: Realiza la migración sin pedir confirmación.
  * ```--keep-backup```: Conserva la copia de seguridad generada durante la migración.
  * ```--recreate-database```: Elimina la base de datos y la vuelve a crear si es necesario recuperar la copia de seguridad.

Los parámetros son los mismos que con la migración habitual, incluyendo ```-n``` para realizarla sin pedir confirmación.
