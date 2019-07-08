UPGRADE 2.0
===========

Este documento contiene una lista de los cambios más relevantes entre la versión 1.x.x
de la plataforma y la 2.0.

Lea con atención porque algunos de ellos implican la imposibilidad de bajar de versión 
una vez actualizada la base de datos.

FP dual organizada en proyectos
-------------------------------

A partir de la versión 2.0, la información y el seguimiento de la FP dual no está asociada
directamente al curso académico. De hecho, lo habitual es que un mismo proyecto de FP dual
tenga una duración de dos cursos académicos y eso podría provocar problemas con el modelo
de datos anterior.

Al actualizar la base de datos a cualquier versión 2.x, la información de FP dual será
migrada al nuevo modelo, creándose para ello de forma automática un proyecto por cada
enseñanza asociada a la dual y curso académico. Este proyecto luego podrá ampliarse con 
los módulos profesionales y el alumnado del segundo año de vigencia sin problemas.

**Importante: Esta migración no es reversible. Una vez convertido en proyecto no se podrá
volver hacia atrás sin perder información, por eso cualquier intento de volver al modelo
de datos anterior generará un error. Cree una copia de seguridad de la base de datos antes
de migrar de una versión 1.x a una versión 2.x**

Rol de coordinador/a de FP dual
-------------------------------
La nueva organización de la dual basada en proyectos permite asignar a cada uno de ellos
un usuario que actuará de coordinador/a para el mismo. Con el cambio no tiene sentido
asignar un rol general a la organización para esta función.

Al migrar a la versión 2.0, uno (y sólo uno) de los usuarios que tengan asignado el rol
será elegido como coordinador/a de los proyectos de FP dual que se generen automáticamente.

Responsable de seguimiento de FP dual
-------------------------------------
En el modelo de datos anterior no existía explícitamente la figura del tutor docente o
responsable docente de seguimiento, pues ese papel lo ejercía la coordinación de FP dual.
Dado que es un hecho que en muchos centros educativos sí existe un docente distinto al
que ostenta la coordinación que realiza el seguimiento de ciertos estudiantes, se ha 
añadido al convenio de colaboración esta figura. Al realizar la migración se establecerá
por defecto con el coordinador/a del proyecto de FP dual en cuestión.

**NOTA: Para que funcione la migración el coordinador/a de FP dual debe ser un profesor o
la migración fallará.**