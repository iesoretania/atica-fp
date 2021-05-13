CHANGELOG
=========

Este documento es un registro de los cambios más relevantes hechos a la plataforma
en la distintas versiones de la misma.

3.x.x (xxxx-xx-xx)
------------------
* fix: Intento de arreglar la autenticación con Séneca si la contraseña tiene caracteres extraños
* chore: Actualizada la API (v2) para facilitar la implantación de una aplicación Android
* chore: Calcular porcentaje en el registro de actividades de FCT sobre las horas reales del calendario
* fix: Solucionado un problema al llamar a un método

3.5.3 (2021-04-07)
------------------
* fix: Arreglada activación de estudiantes matriculados

3.5.2 (2021-03-21)
------------------
* fix: Arreglado un problema al guardar el acuerdo

3.5.1 (2021-03-21)
------------------
* fix: Solucionado un problema al registrar una visita en dual
* fix: Ahora se pueden asignar antiguos estudiantes como tutores laborales en la FCT

3.5.0 (2020-10-20)
------------------
* feat: Copiar programa formativo desde otro proyecto de FP dual
* feat: Permitir incluir las empresas colaboradoras al copiar desde otro proyecto
* feat: Solo permitir evaluar el desempeño en las concreciones que han sido marcadas como hechas

3.4.0 (2020-09-22)
------------------
* chore: Las visitas se listan ahora agrupadas por docente
* fix: Eliminar los datos asociados al borrar convenios de FP dual
* fix: Solucionado el problema de filtrar por tutor docente en el seguimiento
* fix: Evitar preguntar cuando un usuario tiene varias pertenencias a la misma organización
* fix: Impedir acceder a las visitas de otros tutores docentes
* feat: Generar informe de visitas del tutor docente de FCT
* chore: Mostrar el botón del informe de visitas solamente si hay alguna
* fix: Aplicar las plantillas PDF correctamente a partir de la segunda página
* fix: Permitir que el jefe de departamento pueda consultar las visitas
* fix: Solucionado problema con la actualización del informe del tutor/a de FCT
* chore: Quitar etiquetas de versión si están vacías
* chore: Mostrar actividades no completadas en calendario de FCT
* chore: Dejar la antigua encuesta de seguimiento de FP dual como satisfacción de centro
* fix: Solucionados problemas con los informes de encuestas de FP dual
* fix: Mostrar correctamente el mensaje cuando se pide informe y no hay encuesta asignada
* fix: El último día se puede marcar que se ha visitado al estudiante en la FCT
* chore: Permitir generar el informe de visitas de FCT aunque no haya
* feat: Generar informe de dietas de desplazamiento en la FCT
* feat: Permitir copiar la información de las materias de un curso académico a otro
* feat: El programa formativo de FCT se puede copiar desde otra convocatoria de la misma enseñanza

3.3.0 (2020-06-08)
------------------
* fix: Solucionado un problema de permisos al acceder al seguimiento de la FCT
* fix: No contar de forma múltiple las dietas que visiten más de un acuerdo
* chore: Mostrar solo los días con actividades en el informe de acreditación de asistencia
* fix: Solucionado un problema de redirección en algunas rutas
* feat: Mostrar estadísticas de actividades en el calendario de seguimiento de FCT
* feat: Generar registro de actividades de seguimiento de la FCT
* fix: Solucionado problema con un enlace que dirige al calendario de seguimiento de FCT
* fix: Modificado el listado de tutores en el registro de actividades
* feat: API provisional para el seguimiento de actividades de la FCT
* chore: Mejorado aspecto del registro de actividades de FCT

3.2.0 (2020-04-27)
------------------
* feat: Registro de desplazamientos para la FCT
* feat: Nuevo rol de responsable económico (secretario/a del centro educativo)
* fix: Solucionar orden de fechas en el informe semanal
* chore: Mostrar los criterios de evaluación del programa formativo de forma detallada
* fix: Evitar que se envíe el formulario al registrar un itinerario
* fix: Solucionado problema al eliminar resultados de aprendizaje
* fix: Corregido error al crear una nueva convocatoria de FCT si no se es administrador
* fix: Solucionado problema al generarse el botón "Ir a hoy" en el seguimiento de la FCT
* chore: Ordenar los resultados de aprendizaje por código en los programas formativos
* chore: Ordenar los criterios de evaluación por código en los programas formativos
* fix: Eliminar un acuerdo de colaboración afecta ahora también a su calendario
* fix: Mostrar correctamente el alumnado al especificar la sede visitada

3.1.0 (2020-03-09)
------------------
* feat: Gestión de las visitas a centros de trabajo de la FCT
* fix: Cambiar la fecha de una visita actualiza el listado de alumnado
* fix: Mostrar tutor laboral en la firma del programa formativo de la FCT
* feat: Un acuerdo de colaboración puede ahora tener múltiples estudiantes

3.0.0 (2020-03-04)
------------------
* feat: Soporte inicial del seguimiento de Formación en Centros de Trabajo (FCT)
* feat: Gestión de los acuerdos de colaboración de FCT
* feat: Gestión de los calendarios de los acuerdos de colaboración
* feat: Ahora se pueden especificar los criterios de evaluación de los RA
* feat: Gestión de las actividades del programa formativo
* feat: Listado de los acuerdos para realizar el seguimiento
* feat: Seguimiento de actividades en el calendario
* feat: Los formularios que permiten importar masivamente pueden ahora exportar
* feat: Cumplimentación del informe del tutor/a laboral
* feat: Generación del informe del tutor/a laboral
* feat: Generación del programa formativo individualizado de la FCT
* feat: Eliminada opción de cabecera y pie de página del centro educativo
* feat: Implementadas plantillas predeterminadas (vertical y apaisada) por curso académico
* feat: Permitir dar de alta múltiples acuerdos de colaboración

2.2.3 (2020-02-24)
------------------
* fix: Solucionado un problema al modificar o dar de alta un nuevo convenio

2.2.2 (2020-02-21)
------------------
* fix: Confirmar que se tiene permiso para bloquear jornadas
* fix: Arreglado el registro de nuevas personas por DNI/NIE

2.2.1 (2020-02-21)
------------------
* chore: Ajustado texto de faltas de asistencia para clarificar significado
* fix: Afinados permisos del tutor de grupo a la hora de bloquear/desbloquear

2.2.0 (2020-02-21)
------------------
* chore: Los gerentes de empresas y sedes se seleccionan mediante DNI
* fix: Solucionado un problema con las encuestas de empresa y coordinación
* feat: Gestión de plantillas de informes en PDF
* feat: Hay una sección en las jornadas para indicar actividades adicionales realizadas
* feat: Bloqueo/desbloqueo de semanas completas y considerar semanas de 7 días
* feat: Generación de un informe semanal de actividades realizadas

2.1.0 (2020-01-30)
------------------
* feat: La configuración de evaluación de concreciones se realiza ahora por proyecto, no por curso académico

2.0.5 (2020-01-16)
------------------
* feat: Los docentes de un grupo de FP dual pueden ver las actividades de seguimiento

2.0.4 (2019-12-27)
------------------
* feat: Mostrar todos los datos del convenio al copiar un calendario
* fix: Corregido cambio de fecha al copiar un calendario
* fix: Un proyecto debe tener al menos un grupo de alumnado asociado
* fix: Listado corregido a la hora de eliminar una empresa

2.0.3 (2019-11-20)
------------------
* fix: Solucionado un problema cuando el coordinador de proyecto crea un convenio

2.0.2 (2019-11-18)
------------------
* fix: Restringir el número de horas de una jornada a un mínimo de cero
* fix: Solucionado problema al guardar una valoración del desempeño
* fix: Solucionado problema al guardar una encuesta
* fix: Mostrar convenios del responsable laboral

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
