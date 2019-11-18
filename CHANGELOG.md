CHANGELOG
=========

Este documento es un registro de los cambios más relevantes hechos a la plataforma
en la distintas versiones de la misma.

2.0.x (xxxx-xx-xx)
------------------
* fix: Restringir el número de horas de una jornada a un mínimo de cero

2.0.1 (2019-11-18)
------------------
* fix: Importar la encuesta de satisfacción del centro desde la versión 1
* fix: Eliminado rol global de coordinador/a de FP
* fix: Arreglado un problema en el formulario al mostrar los datos de un docente

2.0.0 (2019-11-18)
------------------
* feat: ¡IMPORTANTE! Soporte de FP dual basado en proyectos, no en cursos académicos
* feat: Las visitas a los centros de trabajo pueden estar asociadas a proyectos y/o estudiantes en alternancia
* feat: Existe el rol de responsable de seguimiento de un convenio
* feat: Existe el coordinador/a de un proyecto de FP dual
* feat: No es necesario seleccionar las enseñanzas/grupos/módulos de FP dual
* feat: No es necesario que exista una materia para importar actividades
* feat: Se pueden copiar encuestas a la hora de crearlas
* feat: Encuesta de responsable de seguimiento por curso académico y proyecto 
* feat: Las preguntas de las encuestas usan editor HTML
* feat: Todos los informes se generan por proyecto
* feat: La mayoría de los listados pueden filtrarse por curso académico
* feat: Separación del alumnado de un proyecto por grupos
* fix: Permitir paginación cuando se filtra un listado
* chore: Renombrada sección de programas formativos de empresa
* fix: Solucionado error producido por la ausencia de jornadas en el seguimiento
* chore: Separados los menús de los bloques de educación y dual del core
* chore: Mejorada la navegabilidad al modificar una visita
* feat: Los centros de trabajo no dependen del curso académico

1.5.3 (2019-11-17)
------------------
* fix: Solucionado error al mostrar un calendario sin jornadas
* fix: Ahora aparecen los estudiantes al generar convenios nuevos 

1.5.2 (2019-10-29)
------------------
* fix: Solucionados diversos problemas en la creación de los contenedores con Docker

1.5.1 (2019-06-13)
------------------
* feat: Incluir estadísticas generales en el informe de asistencia

1.5.0 (2019-06-13)
------------------
* feat: Creada sección de generación de informes
* feat: Listar los acuerdos del mismo estudiante ordenados por fecha de comienzo
* feat: Generación del informe de satisfacción de alumnado en la FP dual
* feat: Generación del informe de satisfacción de empresas en la FP dual
* feat: Generación del informe de satisfacción del seguimiento de FP dual
* feat: Generación del informe de acreditación de asistencia del alumnado
* feat: Generación del informe de reuniones de tutorización
* feat: Generación del informe resumen de asistencia del alumnado
* feat: Generación del informe resumen de evaluación
* feat: Personalización del encabezado y pie de página de los informes

1.4.0 (2019-06-10)
------------------
* fix: Mostrar correctamente los días de ausencia
* core: Actualizados componentes a la última versión
* feat: Soporte inicial de encuestas
* feat: Encuestas de alumnado, empresa y profesorado de dual
* feat: Añadir un texto fijo como tipo de respuesta en una encuesta
* feat: Registro de eventos de auditoría para las encuestas
* fix: Al eliminar las respuestas de una encuesta se actualiza correctamente su estado
* feat: La encuesta de profesorado es solo para quien hace seguimiento
* fix: Restringir las encuestas de seguimiento al usuario propio
* feat: Aviso de encuestas no cumplimentadas en plazo
* feat: La encuesta de satisfacción de seguimiento se aplica al curso académico
 
1.3.0 (2019-04-07)
------------------
* UX: Eliminada duplicidad de enlaces para salir de la aplicación
* fix: Impedir que el script de Docker falle en Windows
* fix: Permitir actualizar concreciones bloqueadas a los responsables
* feat: Permitir actualizar concreciones a los responsables tras la evaluación
* security: Actualizadas dependencias por seguridad
* fix: Solucionado un problema con la copia de calendarios
* fix: Solucionado error al añadir jornadas en el calendario
* fix: La búsqueda en las visitas incluye también las observaciones
* fix: Corregido un error al modificar fechas en las visitas/reuniones
* fix: Solucionada errata al mostrar organizaciones

1.2.0 (2019-01-28)
------------------
* feat: Tareas de mantenimiento periódicas desde la línea de comandos
* feat: Añadido registro de eventos para auditoría

1.1.x (2019-01-24)
------------------
* UX: Mejorada la pantalla de evaluación del convenio
* fix: Solucionado un problema al restablecer la contraseña vía correo electrónico

1.1.0 (2019-01-22)
------------------
* feat: Auditoría de cambios en la base de datos
* UX: Mostrar estadísticas en el calendario del convenio mediante una barra de progreso desplegable
* feat: Se ha incluido una configuración para permitir el despliegue en Docker de la aplicación
* perf: Se ha optimizado el número de consultas a la base de datos
* UX: Las sedes se gestionan ahora desde el formulario de empresas
* feat: Importación masiva de resultados de aprendizaje mediante un cuadro de texto

1.0 (2019-01-15)
----------------
* Primera versión lista para producción
