UPGRADE 2.0
===========

Este documento contiene una lista de los cambios más relevantes entre la versión 2.0.x
de la plataforma y la 2.1.

Lea con atención porque algunos de ellos implican la imposibilidad de bajar de versión
una vez actualizada la base de datos.

Configuración de evaluación de concreciones basada en proyectos
---------------------------------------------------------------

A partir de la versión 2.1, la configuración de la evaluación de las concreciones
(valoración del desempeño) se realiza de forma independiente en cada proyecto.

Al actualizar la base de datos la versión 2.1, se asignará la configuración de
la evaluación existente al primer proyecto registrado en cada curso académico.

**NOTA: Una vez migrada la base de datos a la versión 2.1, no se podrá volver hacia atrás.
Es recomendable sacar una copia de seguridad de la base de datos antes de subir de versión**
