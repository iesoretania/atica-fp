CHANGELOG
=========

Este documento es un registro de los cambios más relevantes hechos a la plataforma
en las distintas versiones de la misma.

x.x.x (xxxx-xx-xx)
------------------
* fix: Solucionado un problema al registrar acuerdos de colaboración de la FCT
* fix: Solucionado un problema al crear proyectos nuevos de dual y convocatorias de FCT
* chore: Actualizados componentes a la última versión

6.1.2 (2024-03-12)
------------------
* fix: Mostrar versión actualizada
* chore: Mostrar desplegable de selección de centros si hay menos de 8 en vez de menos de 6
* fix: Mostrar encuesta bloqueada para el tutor laboral de FCT si la convocatoria está bloqueada
* chore: Mostrar un candado si la convocatoria de FCT está bloqueada
* chore: Mostrar el candado en algunos listados más
* fix: Mostrar encuestas como bloqueadas si el proyecto de FP dual lo está
* chore: Mostrar un candado si el proyecto de FP dual está bloqueado
* feat: El administrador global puede cambiar los códigos internos de Séneca de departamentos
* fix: Arreglado un problema al mostrar las encuestas del responsable de seguimiento de la FP dual

6.1.1 (2024-02-07)
------------------
* fix: Ajustada migración para funcionar en MariaDB < 10.5.2

6.1.0 (2024-02-06)
------------------
* fix: Solucionada generación de informe de satisfacción de tutor laboral de FCT
* feat: El administrador global puede cambiar los códigos internos de Séneca de materias, cursos y enseñanzas
* feat: Permitir al profesorado del grupo revisar la valoración del desempeño de su alumnado de FP dual

6.0.0 (2023-12-21)
------------------
### CAMBIOS EN LA BASE DE DATOS
* feat: Eliminar centros de trabajo al eliminar una empresa
* chore: Renombrada entidad Visit por Contact en la FCT
* feat: Añadido a la base de datos el seguimiento de las dietas de desplazamiento en FP dual
* feat: Añadida a la base de datos la posibilidad de bloquear convenios y acuerdos
* feat: Gestión de dietas de desplazamiento en la FP dual
* feat: Implementada la lógica de bloqueo de convocatorias de FCT y proyectos de FP dual

5.6.3 (2023-07-07)
------------------
* fix: Los informes de encuestas de tutores laborales de FCT ya se generan correctamente

5.6.2 (2023-06-23)
------------------
* feat: Añadir número de horas realizadas en el informe de acreditación de asistencia

5.6.1 (2023-06-11)
------------------
* feat: Listar los niveles de desempeño y su descripción en la pantalla de evaluación
* fix: El listado de contactos con empresas de FP dual ahora pagina correctamente

5.6.0 (2023-05-09)
------------------
* feat: Eliminar un proyecto de FP dual elimina automáticamente toda su información
* feat: Eliminar una convocatoria de FCT elimina automáticamente toda su información
* chore: Listar correctamente centros educativos a eliminar
* chore: Mostrar el número de versión en el pie de página de la pantalla de entrada
* fix: Arreglado problema al ordenar las preguntas de una encuesta

5.5.0 (2023-05-03)
------------------
* fix: Eliminar una jornada de un calendario de FCT elimina todas las actividades del estudiante
* fix: Solucionado un problema al eliminar un convenio de FP dual
* feat: Navegación entre jornadas de seguimiento de FP dual
* feat: Navegación entre jornadas de seguimiento de FCT
* feat: Permitir visualizar información del convenio en el calendario de seguimiento de FP dual
* feat: Permitir visualizar información del acuerdo en el calendario de seguimiento de FCT
* fix: Arreglar navegación entre jornadas cuando hay zonas horarias

5.4.5 (2023-03-23)
------------------
* fix: Ahora se pueden añadir estudiantes nuevos a un acuerdo de colaboración de FCT ya existente
* feat: Es posible copiar un programa formativo de FCT desde las concreciones de un proyecto de FP dual
* chore: Listar los grupos de alumnado en el listado de niveles
* chore: Listar los niveles en el listado de enseñanzas
* fix: Arreglado problema por el que ciertos acuerdos de colaboración no se listaban en las dietas de desplazamiento
* fix: Solucionado un problema con la paginación de los acuerdos de la FCT de una convocatoria

5.4.4 (2023-03-14)
------------------
* feat: Incorporada la posibilidad de ignorar los días no lectivos al añadir jornadas a un calendario

5.4.3 (2023-03-08)
------------------
* feat: Cuando se importa alumnado, buscar la unidad también por nombre, no solo por código
* fix: Solucionado un problema al eliminar elementos en las escalas de valoración de concreciones

5.4.2 (2023-02-10)
------------------
* feat: Mostrar concreciones deshabilitadas en las estadísticas de seguimiento y en el resumen de evaluación
* feat: Ahora borrar algunos elementos intenta eliminar también otros datos asociados (materias, enseñanzas, etc.)
* fix: Arreglado la copia incorrecta de RAs en los programas formativos de FP dual
* fix: Arreglada la eliminación de acuerdos de colaboración de FCT con sus datos asociados

5.4.1 (2023-02-08)
------------------
* fix: Solucionado un problema que impedía marcar todas las actividades de un convenio al modificarlo

5.4.0 (2023-02-07)
------------------
* fix: Solucionado problema con ciertos accesos si una enseñanza de dual no tiene asignado departamento
* fix: Arreglado un problema al añadir concreciones a un convenio de FP dual
* feat: Ahora se pueden seleccionar o deseleccionar todas las concreciones en los convenios de FP dual
* feat: Ahora se pueden seleccionar o deseleccionar todas las actividades en los acuerdos de FCT

5.3.0 (2023-01-09)
------------------
* fix: Solucionado el error de generación de documentos PDF muy complejos
* feat: Ahora se pueden añadir comentarios a las concreciones de un convenio concreto y desactivar elementos
* fix: Arreglado el problema de acceso al seguimiento por parte del equipo educativo del estudiante de dual

5.2.2 (2022-11-28)
------------------
* fix: Modificada una consulta de encuestas de satisfacción de responsable laboral para evitar un error del ORM
* fix: Solucionado un problema al trabajar con las convocatorias de la FCT
* fix: Mejorada la lógica de detección de importación de profesorado ya existente
* fix: Autenticar en local si no tiene éxito la autenticación por Séneca
* fix: Solucionado un fallo al intentar importar los RAs y CEs de módulos no registrados previamente

5.2.1 (2022-07-03)
------------------
* fix: Solucionado informe de actividades de la FCT si un estudiante participa en más de una convocatoria
* fix: Solucionado otro error con el informe
* fix: Mostrar correctamente el listado de desplazamientos de FCT
* style: Eliminada animación innecesaria al cargar las páginas
* fix: Mostrar correctamente las estadísticas de encuestas cumplimentadas en FCT y dual

5.2.0 (2022-06-13)
------------------
* feat: Implementadas encuestas de la FCT
* feat: Se pueden generar informes de las encuestas de FCT
* fix: Copiar las horas por defecto de entrada y salida del acuerdo en las jornadas nuevas de FCT
* fix: Solucionado un problema que impedía acceder a usuarios relacionados solamente con la FP dual
* fix: Solucionado el problema de acceso de los responsables laborales adicionales a las encuestas de FCT
* fix: Los tutores docentes también pueden cumplimentar las encuestas del tutor laboral de FCT

5.1.0 (2022-05-23)
------------------
* chore: No mostrar más de 5 centros educativos en el desplegable de cambio de centro
* fix: Corregido un problema al listar convenios de colaboración con ciertos perfiles
* chore: Renombrar visitas a empresas de FP dual por contacto con empresas
* feat: Ahora se puede especificar el tipo de contacto con la empresa en la FP dual
* feat: El centro puede configurar los tipos de contacto permitidos en cada curso académico
* feat: En el listado de contactos se puede filtrar por tipos de contacto
* fix: Arreglado el filtrado de convocatorias de FCT
* fix: Solucionado informe de reuniones de tutorización de FP dual
* fix: Solucionado informe de asistencia de FP dual
* fix: Solucionados los informes de evaluación y de programa formativo de FP dual
* fix: A los usuarios que autentican externamente no se les obliga a cambiar la contraseña
* feat: Mostrar errores más descriptivos cuando hay un problema con la importación de ficheros CSV
* chore: Optimizada consulta de listado de acuerdos de FCT
* fix: Los responsables de FP dual pueden acceder al resumen de evaluación de todo el alumnado
* fix: Solucionado error al modificar encuestas sin fecha de comienzo o finalización
* feat: Permitir añadir información adicional al evaluar el desempeño de un estudiante de dual
* feat: Incluir más tipos de preguntas en las encuestas (números del 0-5 y 0-10)
* feat: Mostrar el número de convenio de FP dual en el listado del proyecto
* feat: Generación de un informe de contactos para responsables de seguimiento de FP dual
* feat: Mejorado el flujo de trabajo del informe de contactos de responsables de seguimiento
* feat: Generación de un informe de contactos para centros de trabajo de la FP dual
* fix: Solucionado un problema potencial con la apertura de informes en pestañas nuevas
* feat: Permitir añadir formato a la información de un contacto de FP dual

5.0.0 (2022-04-20)
------------------
* fix: Solucionar problema con la importación de profesorado
* fix: Problema con el informe de asistencia arreglado
* fix: Arregladas las plantillas de informes de encuestas de FP dual
* fix: Comprobar correctamente las encuestas si no están definidas
* fix: Si se intenta forzar un cambio de contraseña no válido, redirigir
* fix: Solucionado otro problema con una plantilla no actualizada
* fix: Intento de solucionar la copia del calendario
* fix: Solucionado problema con la administración de las organizaciones
* feat: Eliminada separación entre personas y usuarios para simplificar la gestión
* chore: Aplicado RectorPHP para actualizar el código
* chore: Eliminado formulario y controlador no usados en la gestión de FCT
* chore: Limpieza de código
* feat: Nuevo informe de resumen de programa formativo de FP dual
* fix: Arregladas estadísticas de actividades en la FCT
* feat: Búsqueda de personas por correo electrónico o identificador
* fix: Asignar usuarios automáticamente al importar alumnado
* feat: Autenticación segura con Séneca. Se permite autenticar alumnado y padres
* fix: Se evita un error cuando se bloquean jornadas pero no hay ninguna actividad realizada
* fix: Mostrar mensaje correcto cuando no hay jornadas bloqueadas en la FCT
* chore: Usar la fecha de fin del acuerdo en el informe de registro de actividades de la FCT
* feat: Mostrar otras actividades anotadas en el registro de actividades de la FCT
* feat: Un administrador puede impersonar un usuario que no haya cambiado la contraseña
* feat: Un acuerdo de FCT puede ser seguido por un tutor docente y laboral adicional opcional
* feat: Importación de usuarios PASEN para el alumnado, habilitando la autenticación por Séneca
* feat: Ahora se pueden modificar los datos del profesorado del centro
* feat: Se pueden incorporar nuevos docentes en un curso académico manualmente
* fix: Solucionado problema al modificar usuarios
* fix: Arreglado un problema con la copia de los programas formativos de los proyectos de FP dual
* feat: Creación de usuarios administradores y centros educativos desde la consola de comandos
* fix: Arreglada validación del perfil de usuario
* fix: Arreglado problema con la creación de acuerdos y convenios
* feat: Permitir elegir cualquier acuerdo de colaboración al copiar calendario de FCT
* feat: Opción de crear una instancia Docker con datos de demostración
* feat: Un convenio de FP dual puede ser seguido por responsables de seguimiento opcionales
* fix: Solucionar un problema al añadir un nuevo responsable o tutor laboral
* feat: Importar resultados de aprendizaje y criterios de evaluación desde Séneca
* fix: Corregido un error de permisos para permitir el despliegue en Docker para Windows
* feat: Incluido un desplegable de cambio rápido de centro educativo
* fix: Mostrar el fichero seleccionado en los formularios que permiten subir ficheros
* fix: Arregladas algunas traducciones que se modificaron de forma no intencionada
* feat: Las encuestas de FP dual se realizan únicamente una vez por curso académico y proyecto
* fix: Los tutores docentes adicionales pueden ahora registrar visitas a estudiantes correctamente
* style: Mejorada la navegación por los menús cuando se usan pantallas pequeñas
* fix: Mostrar correctamente los resultados de las encuestas de FP dual al no indicar curso académico
* feat: Permitir forzar la sobreescritura de los nombres de usuario al importar estudiantes

4.1.0 (2022-02-13)
------------------
* docs: Mejoradas las instrucciones de actualización
* fix: Ajustar .env para que el entorno por defecto sea producción y el motor de bases de datos, MySQL
* fix: Mostrar la fecha del token de cambio de correo electrónico en la zona horaria correcta
* style: Ajustes realizados al CSS principal y eliminación de estilos no utilizados
* chore: Actualizar npm a la última versión en Docker
* fix: Mostrar todos los profesores de FP dual en la lista cuando un responsable crea una visita
* fix: Mostrar correctamente los proyectos al registrar una visita de FP dual
* style: Ajustar el tamaño de los campos del formulario de entrada
* fix: Arreglar selección de grupos y docentes al registrar una visita de FP dual
* fix: Incluir siempre en la lista de profesorado visitante a los responsables de seguimiento de FP dual
* fix: Desactivar el defer en los scripts para permitir la carga de personas en los formularios
* fix: Ajustado script de Docker para arrancar en producción
* fix: Arreglado error 500 que ocurría al intentar generar informes
* fix: Solucionado un problema de incompatibilidad con Twig 2.0
* fix: Eliminado uso obsoleto del codificador de contraseñas
* feat: Permitir abrir informes en una nueva pestaña/ventana
* fix: Solucionado problema con el informe de asistencia de FP dual

4.0.1 (2022-01-20)
------------------
* docs: Añadidas instrucciones críticas para actualizar la configuración

4.0.0 (2022-01-20)
------------------
* chore: La API devuelve los días del calendario de FCT que aún no se ha cumplimentado
* fix: Devolver el 'id' correcto de la jornada de trabajo en la API de seguimiento de la FCT
* feat: Desde la API se puede modificar la hora de inicio/fin de la jornada laboral en la FCT
* fix: Mostrar siempre el alumnado de FP dual al responsable del seguimiento
* chore: Actualizar código a la versión 8.* de MPDF y del componente de migraciones
* chore: Actualizar dependencias para PHP >=7.2 y NodeJS >=16
* chore: Arreglar mensajes "deprecated" para preparar la migración a Symfony 4.4
* feat: Actualizado el estilo de la interfaz para plegar el menú lateral
* feat: Modificada la configuración de Docker para ejecutar la aplicación con los últimos cambios
* docs: Actualizado CHANGELOG, README y UPGRADE con los cambios más relevantes

3.5.4 (2021-05-26)
------------------
* fix: Intento de arreglar la autenticación con Séneca si la contraseña tiene caracteres extraños
* chore: Actualizada la API (v2) para facilitar la implantación de una aplicación Android
* chore: Calcular porcentaje en el registro de actividades de FCT sobre las horas reales del calendario
* fix: Solucionado un problema al llamar a un método
* fix: Arreglado el cambio de hora cuando se registraba un desplazamiento en la FCT

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
