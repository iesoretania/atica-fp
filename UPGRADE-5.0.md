UPGRADE 5.0
===========

**Atención: Se recomienda sacar copia de seguridad de la base de datos antes de migrar
a la nueva versión, no es posible revertir la migración a versiones anteriores.**

Este documento contiene una lista de los cambios más relevantes entre la versión 4.x.x
de la plataforma y la 5.0.

Lea con atención porque algunos de ellos implican la imposibilidad de volver a versiones anteriores
manteniendo la base de datos.

Cambios en la configuración
---------------------------
### Configuración local
Es necesario eliminar la línea APP_EXTERNAL_URL en el fichero `.env.local` si existe y se usa autenticación
externa desde Séneca.

Simplificación de la gestión de usuarios
----------------------------------------
A partir de esta versión se crean usuarios automáticamente para las personas implicadas, así
que no será necesario activar o desactivar el acceso a la plataforma al alumnado.

Autenticación con Séneca más segura
-----------------------------------
Ahora se cifra el envío de contraseña y, además, se permite autenticar alumnado (usuario iPasen).
**Debido a esto, la URL de autenticación ha cambiado**. Por favor, comprueba que la línea que comienza
por APP_EXTERNAL_URL no existe en el fichero `.env.local` o la autenticación de Séneca
no funcionará.

Nuevos informes
---------------
Se ha añadido un nuevo informe resumen de programa formativo de FP dual, ideal para incluir
en la documentación de los proyectos.

Importación de usuarios PASEN para el alumnado
----------------------------------------------
Existe la posibilidad de importar los usuarios PASEN/IdEA del alumnado con el objeto de permitir
autenticar a los estudiantes con el mismo usuario/contraseña usados en los servicios de la
Consejería (Moodle, iPasen, etc.)

El fichero necesario se exporta desde Séneca con el perfil de Dirección.

Importación de RAs y criterios de evaluación
--------------------------------------------
Ahora se pueden importar los RAs y criterios de evaluación de módulos profesionales a partir
de los ficheros generados desde Séneca.

Tutores adicionales en los acuerdos de FCT
------------------------------------------
Ahora es posible especificar un tutor docente o laboral adicional que tendrá
las mismas posibilidades que el principal, que es el que aparecerá en las
pantallas e informes.

Esto permite asignar profesorado sustituto o de apoyo, así como soportar el caso
en el que el seguimiento real por parte de la empresa lo realice otro empleado.
