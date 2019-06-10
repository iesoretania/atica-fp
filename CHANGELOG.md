CHANGELOG
=========

Este documento es un registro de los cambios más relevantes hechos a la plataforma
en la distintas versiones de la misma.

1.4.x (2019-xx-xx)
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
 