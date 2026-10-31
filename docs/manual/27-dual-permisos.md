# FP dual: permisos

Este capítulo es para **todo el mundo**: resume qué puede hacer cada perfil en el módulo de FP dual. Igual que las
demás matrices, **no está escrita de memoria**: cada versión de la aplicación se prueba entrando con un usuario de
cada perfil y abriendo cada pantalla de la tabla; si el código cambia un permiso, la prueba falla y obliga a
actualizar el manual.

## Cómo leer la tabla

- Cada **columna** es un perfil; cada **fila**, una acción. **✅** significa que el perfil puede hacerlo y **—**,
  que no.
- Los perfiles están medidos **respecto a un convenio o proyecto concreto**. «Responsable de seguimiento y tutoría
  de grupo» es quien hace el seguimiento del estudiante del convenio y tutoriza su grupo; «Responsable de seguimiento
  de otro convenio» es otro docente con seguimiento, pero de un convenio distinto. Una misma persona puede estar en
  una columna respecto a un convenio y en otra respecto a otro.
- Una persona puede **sumar** perfiles.
- «**Abrir**» una pantalla no siempre significa poder **modificarla**: algunas se abren en solo lectura. Los matices
  están bajo la tabla.

## Los perfiles

| Perfil | Quién es |
| --- | --- |
| **Administración del centro** | Administración local del centro ([capítulo 4](04-administrar-el-centro.md#administracion-local)) |
| **Coordinación del proyecto** | La persona designada como coordinadora del proyecto |
| **Jefatura de departamento** | Jefatura del departamento al que pertenece la enseñanza del proyecto |
| **Responsable de seguimiento y tutoría de grupo** | Docente responsable del seguimiento del convenio y tutor o tutora del grupo del alumno |
| **Responsable de seguimiento de otro convenio** | Docente con seguimiento de FP dual, pero sin relación con el convenio de la tabla |
| **Profesorado del grupo** | Docente que da clase al grupo del estudiante y no tiene otro cargo |
| **Alumnado del convenio** | Estudiante del convenio |
| **Responsable laboral del convenio** | Persona de la empresa responsable del convenio |
| **Responsable económico** | Secretaría del centro ([capítulo 26](26-dual-responsable-economico.md)) |
| **Resto del personal** | Cualquier otra persona del centro (por ejemplo, la gestión de la FFEOE) |

## Matriz de permisos

| Puede... | Administración del centro | Coordinación del proyecto | Jefatura de departamento | Responsable de seguimiento y tutoría de grupo | Responsable de seguimiento de otro convenio | Profesorado del grupo | Alumnado del convenio | Responsable laboral del convenio | Responsable económico | Resto del personal |
| --- | :-: | :-: | :-: | :-: | :-: | :-: | :-: | :-: | :-: | :-: |
| **Proyectos** |  |  |  |  |  |  |  |  |  |  |
| Ver el listado de proyectos | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Crear un proyecto | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Editar un proyecto | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Gestionar el alumnado de un proyecto | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Eliminar proyectos | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| **Programa formativo** |  |  |  |  |  |  |  |  |  |  |
| Ver las empresas de un proyecto | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Incorporar una empresa al proyecto | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Editar el programa de una empresa | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Eliminar empresas del proyecto | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Ver las actividades de un proyecto | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Crear una actividad | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Editar una actividad | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Copiar actividades de otro proyecto | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Eliminar actividades | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Ver las concreciones de una actividad | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Editar una concreción | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| **Convenios** |  |  |  |  |  |  |  |  |  |  |
| Ver los convenios de un proyecto | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Crear un convenio | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Editar un convenio | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Ver el calendario de un convenio | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Eliminar convenios | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| **Seguimiento diario** |  |  |  |  |  |  |  |  |  |  |
| Ver el seguimiento del alumnado | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — | — |
| Abrir el calendario de seguimiento de un alumno | ✅ | ✅ | ✅ | ✅ | — | ✅ | ✅ | ✅ | — | — |
| Descargar la hoja de asistencia | ✅ | ✅ | ✅ | ✅ | — | ✅ | ✅ | ✅ | — | — |
| **Valoración y evaluación** |  |  |  |  |  |  |  |  |  |  |
| Ver los convenios evaluables | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — | ✅ | — | — |
| Abrir la valoración de actividades de un convenio (consulta o modificación según el perfil) | ✅ | ✅ | ✅ | ✅ | — | ✅ | — | ✅ | — | — |
| Ver el resumen de evaluación | ✅ | ✅ | ✅ | ✅ | — | ✅ | — | — | — | — |
| Obtener el informe de resultados de un alumno | ✅ | ✅ | ✅ | ✅ | — | ✅ | — | — | — | — |
| **Reuniones de tutorización** |  |  |  |  |  |  |  |  |  |  |
| Ver las reuniones | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — | — | — | — |
| Registrar una reunión | — | ✅ | ✅ | ✅ | ✅ | ✅ | — | — | — | — |
| Editar una reunión | ✅ | ✅ | ✅ | ✅ | — | — | — | — | — | — |
| Eliminar reuniones | ✅ | — | — | ✅ | — | — | — | — | — | — |
| **Contactos con empresas** |  |  |  |  |  |  |  |  |  |  |
| Ver los contactos con empresas | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — | — | — | — |
| Registrar un contacto | — | ✅ | ✅ | ✅ | ✅ | ✅ | — | — | — | — |
| Editar un contacto | ✅ | ✅ | ✅ | ✅ | — | — | — | — | — | — |
| Eliminar contactos | ✅ | — | ✅ | ✅ | — | — | — | — | — | — |
| Ver el informe de contactos del responsable de seguimiento | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — | — | — | — |
| Ver el informe de contactos por centro de trabajo | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — | — | — | — |
| **Gastos de desplazamiento** |  |  |  |  |  |  |  |  |  |  |
| Ver el resumen de gastos del profesorado | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — | — | ✅ | — |
| Ver los gastos de un docente | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — | — | ✅ | — |
| Registrar un gasto | ✅ | ✅ | ✅ | ✅ | — | — | — | — | ✅ | — |
| Descargar el informe de gastos | ✅ | ✅ | ✅ | ✅ | — | — | — | — | ✅ | — |
| Eliminar gastos | ✅ | ✅ | ✅ | ✅ | — | — | — | — | ✅ | — |
| **Encuestas** |  |  |  |  |  |  |  |  |  |  |
| Ver las encuestas de alumnado | ✅ | ✅ | ✅ | ✅ | ✅ | — | ✅ | — | — | — |
| Abrir la encuesta de alumnado de un convenio | ✅ | ✅ | ✅ | ✅ | — | — | ✅ | — | — | — |
| Ver las encuestas de empresa | ✅ | ✅ | ✅ | ✅ | ✅ | — | — | ✅ | — | — |
| Abrir la encuesta de empresa de un convenio | ✅ | ✅ | ✅ | ✅ | — | — | — | ✅ | — | — |
| Ver las encuestas de centro | ✅ | ✅ | ✅ | ✅ | ✅ | — | — | — | — | — |
| Abrir la encuesta de centro de un docente | ✅ | ✅ | ✅ | ✅ | — | — | — | — | — | — |
| **Informes del proyecto** |  |  |  |  |  |  |  |  |  |  |
| Acceder al apartado de informes | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Obtener el informe de reuniones | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Obtener el informe de asistencia | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Obtener el informe de evaluación | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Obtener el informe del programa formativo | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Obtener el informe de la encuesta de alumnado | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Obtener el informe de la encuesta de empresas | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |
| Obtener el informe de la encuesta de centro | ✅ | ✅ | ✅ | — | — | — | — | — | — | — |

## Matices

- **Gestión.** La administración del centro gestiona todos los proyectos. **La coordinación y la jefatura**
  gestionan solo los de su proyecto o su departamento: la tabla mide el caso en que el proyecto es suyo.
- **Valoración.** «Abrir la valoración» es **consulta** para el profesorado del grupo y **consulta y modificación**
  para el responsable laboral, el responsable de seguimiento, la tutoría de grupo, la coordinación y la jefatura. Con
  el proyecto bloqueado o el convenio de un curso anterior, todos la consultan en solo lectura (la administración
  del centro puede seguir modificándola).
- **Calendario y jornadas.** Las pueden **modificar** el alumnado, el responsable laboral, el responsable de
  seguimiento, la tutoría de grupo, la coordinación, la jefatura y la administración. El profesorado del grupo solo
  las consulta.
- **Resumen de evaluación.** El profesorado del grupo solo ve el alumnado al que da clase y solo sus módulos; la
  tutoría de grupo, la coordinación y la jefatura ven todos los módulos de su alumnado. El responsable de seguimiento
  que no tutoriza el grupo no ve el resumen.
- **Contactos.** Los puede **modificar y eliminar** solo quien los registró (y la administración del centro). La
  administración del centro no puede registrar contactos si no es profesorado del curso; la coordinación y la
  jefatura sí, si lo son. Los contactos y desplazamientos son tarea del profesorado que hace seguimiento o imparte
  docencia en el proyecto.
- **Gastos.** Cada docente registra y elimina los suyos. La administración, la jefatura, la coordinación y el
  responsable económico pueden registrar, corregir y eliminar los de cualquier docente.
- **Bloqueo del proyecto.** Con el proyecto bloqueado, la jefatura de departamento abre en **solo lectura** las pantallas
  del proyecto, de su programa formativo y de sus empresas; solo la administración y la coordinación pueden
  modificarlas. La tabla mide el proyecto abierto.
- **Encuestas.** Los listados solo contienen lo que corresponde a quien los abre. El alumnado ve su encuesta; la
  empresa, la suya; y las tutorías, coordinación y jefatura, las tres. El profesorado del grupo no participa en ninguna.
- **Reuniones de tutorización.** Solo el profesorado del curso puede registrarlas, así que la administración del
  centro, si no es docente, no puede. Las modifica o elimina únicamente quien las registró (y la administración
  del centro): el resto de perfiles que las ven (coordinación, jefatura o tutoría de grupo) las abren en solo
  lectura, y por eso la tabla marca «Editar» donde se puede abrir la reunión. Las reuniones que ve cada perfil
  están limitadas a su ámbito: las propias, las de sus proyectos o las de su alumnado.
- **Módulo desactivado.** Si el módulo FP dual no está activo en el curso, ningún perfil tiene acceso a él.
