# FCT: permisos

Este capítulo es para **todo el mundo**: resume qué puede hacer cada perfil en el módulo de FCT. Igual que la
[matriz general](09-permisos-generales.md), **no está escrita de memoria**: cada versión de la aplicación se
prueba entrando con un usuario de cada perfil y abriendo cada pantalla de la tabla; si el código cambia un
permiso, la prueba falla y obliga a actualizar el manual.

## Cómo leer la tabla

- Cada **columna** es un perfil; cada **fila**, una acción. **✅** significa que el perfil puede hacerlo y **—**,
  que no.
- Los perfiles de la tabla están medidos **respecto a un acuerdo concreto**. Por ejemplo, «Tutoría docente y de
  grupo» es la tutora del alumno de ese acuerdo; «Tutoría docente de otro acuerdo» es otra docente que también
  hace tutoría de FCT, pero de un acuerdo distinto. Una misma persona puede estar en una columna respecto a
  un acuerdo y en otra respecto a otro.
- Una persona puede **sumar** perfiles: la tutoría docente que además es jefa de departamento tiene los
  permisos de las dos columnas.

## Los perfiles

| Perfil | Quién es |
| --- | --- |
| **Administración del centro** | Administración local del centro ([capítulo 4](04-administrar-el-centro.md#administracion-local)) |
| **Jefatura de departamento** | Jefatura del departamento al que pertenece la enseñanza de la convocatoria |
| **Tutoría docente y de grupo** | Docente responsable del seguimiento del alumnado del acuerdo y tutor o tutora de su grupo |
| **Tutoría docente de otro acuerdo** | Docente de FCT sin relación con el acuerdo de la tabla |
| **Alumnado del acuerdo** | Estudiante incluido en el acuerdo |
| **Tutoría laboral del acuerdo** | Persona de la empresa responsable del acuerdo |
| **Responsable económico** | Secretaría del centro ([capítulo 15](15-fct-responsable-economico.md)) |
| **Resto del personal** | Cualquier otra persona del centro (por ejemplo, la gestión de la FFEOE) |

## Matriz de permisos

| Puede... | Administración del centro | Jefatura de departamento | Tutoría docente y de grupo | Tutoría docente de otro acuerdo | Alumnado del acuerdo | Tutoría laboral del acuerdo | Responsable económico | Resto del personal |
| --- | :-: | :-: | :-: | :-: | :-: | :-: | :-: | :-: |
| **Convocatorias y actividades** |  |  |  |  |  |  |  |  |
| Ver el listado de convocatorias | ✅ | ✅ | — | — | — | — | — | — |
| Crear una convocatoria | ✅ | ✅ | — | — | — | — | — | — |
| Editar una convocatoria | ✅ | ✅ | — | — | — | — | — | — |
| Eliminar convocatorias | ✅ | ✅ | — | — | — | — | — | — |
| Ver las actividades de una convocatoria | ✅ | ✅ | — | — | — | — | — | — |
| Crear una actividad | ✅ | ✅ | — | — | — | — | — | — |
| Copiar actividades de otra convocatoria | ✅ | ✅ | — | — | — | — | — | — |
| Copiar actividades de un proyecto de FP dual | ✅ | ✅ | — | — | — | — | — | — |
| Importar actividades desde texto | ✅ | ✅ | — | — | — | — | — | — |
| Eliminar actividades | ✅ | ✅ | — | — | — | — | — | — |
| **Acuerdos** |  |  |  |  |  |  |  |  |
| Ver los acuerdos de una convocatoria | ✅ | ✅ | — | — | — | — | — | — |
| Crear un acuerdo | ✅ | ✅ | — | — | — | — | — | — |
| Editar un acuerdo | ✅ | ✅ | — | — | — | — | — | — |
| Gestionar el alumnado de un acuerdo | ✅ | ✅ | — | — | — | — | — | — |
| Descargar el programa formativo de un acuerdo | ✅ | ✅ | — | — | — | — | — | — |
| Eliminar acuerdos | ✅ | ✅ | — | — | — | — | — | — |
| Ver el calendario de un acuerdo | ✅ | ✅ | — | — | — | — | — | — |
| **Seguimiento diario** |  |  |  |  |  |  |  |  |
| Ver el seguimiento del alumnado | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — | — |
| Abrir el calendario de seguimiento de un alumno | ✅ | ✅ | ✅ | — | ✅ | ✅ | — | — |
| Descargar la hoja de asistencia | ✅ | ✅ | ✅ | — | ✅ | ✅ | — | — |
| Descargar el resumen de actividades | ✅ | ✅ | ✅ | — | — | — | — | — |
| Ver los informes finales | ✅ | ✅ | ✅ | ✅ | — | ✅ | — | — |
| Abrir el informe final para cumplimentarlo | ✅ | ✅ | ✅ | — | — | ✅ | — | — |
| Descargar el informe final | ✅ | ✅ | ✅ | — | — | ✅ | — | — |
| **Visitas** |  |  |  |  |  |  |  |  |
| Ver el resumen de visitas del profesorado | ✅ | ✅ | ✅ | ✅ | — | — | — | — |
| Ver las visitas de un docente | ✅ | ✅ | ✅ | — | — | — | — | — |
| Registrar una visita | ✅ | ✅ | ✅ | — | — | — | — | — |
| Descargar el informe de visitas | ✅ | ✅ | ✅ | — | — | — | — | — |
| Eliminar visitas | ✅ | ✅ | ✅ | — | — | — | — | — |
| **Gastos de desplazamiento** |  |  |  |  |  |  |  |  |
| Ver el resumen de gastos del profesorado | ✅ | ✅ | ✅ | ✅ | — | — | ✅ | — |
| Ver los gastos de un docente | ✅ | ✅ | ✅ | ✅ | — | — | ✅ | — |
| Registrar un gasto | ✅ | ✅ | ✅ | — | — | — | ✅ | — |
| Descargar el informe de gastos | ✅ | ✅ | ✅ | — | — | — | ✅ | — |
| Eliminar gastos | ✅ | ✅ | ✅ | — | — | — | ✅ | — |
| **Encuestas** |  |  |  |  |  |  |  |  |
| Ver las encuestas de alumnado | ✅ | ✅ | ✅ | ✅ | ✅ | — | — | — |
| Abrir la encuesta de alumnado de un acuerdo | ✅ | ✅ | ✅ | — | ✅ | — | — | — |
| Ver las encuestas de empresa | ✅ | ✅ | ✅ | ✅ | — | ✅ | — | — |
| Abrir la encuesta de empresa de un acuerdo | ✅ | ✅ | ✅ | — | — | ✅ | — | — |
| Ver las encuestas de centro | ✅ | ✅ | ✅ | ✅ | — | — | — | — |
| Abrir la encuesta de centro de un docente | ✅ | ✅ | ✅ | — | — | — | — | — |
| **Informes de la convocatoria** |  |  |  |  |  |  |  |  |
| Acceder al apartado de informes | ✅ | ✅ | — | — | — | — | — | — |
| Ver el listado de informes de las encuestas | ✅ | ✅ | — | — | — | — | — | — |
| Obtener el informe de la encuesta de alumnado | ✅ | ✅ | — | — | — | — | — | — |
| Obtener el informe de la encuesta de empresas | ✅ | ✅ | — | — | — | — | — | — |
| Obtener el informe de la encuesta de centro | ✅ | ✅ | — | — | — | — | — | — |

## Matices

- **Los listados y las fichas son distintos.** Una tutoría docente de otro acuerdo ve los **listados**
  (seguimiento, informes, resumen de visitas y de gastos, encuestas) pero no puede **abrir** el calendario ni la
  ficha de un alumno ajeno. Los listados se filtran por persona, así que solo contienen lo que le corresponde.
- **Jefatura de departamento.** Todo lo de su columna es **solo para las convocatorias de las enseñanzas de su
  departamento**. Los gastos de desplazamiento son la excepción: la jefatura puede registrar, corregir y eliminar
  los de cualquier docente.
- **Tutoría de grupo sin tutoría docente.** Tiene los permisos de la columna «Tutoría docente y de grupo» sobre
  el alumnado de su grupo, **salvo visitas, gastos, registro resumen de actividades y encuesta de centro**,
  que son de quien hace tutoría docente.
- **Tutoría laboral.** Solo ve los acuerdos en los que figura (como tutoría principal o adicional). Puede
  rellenar y descargar su informe, pero **no ve la encuesta del alumnado**.
- **Alumnado.** Puede registrar sus jornadas y descargar su hoja de asistencia, pero no ve el informe de la
  empresa ni el resumen de actividades.
- **Responsable económico.** Su alcance en FCT son los desplazamientos de todo el profesorado, incluida la
  creación, modificación y eliminación.
- **Convocatoria bloqueada o de un curso anterior.** El seguimiento (jornadas, informes y encuestas) es de solo
  lectura para la tutoría docente, la de grupo, la laboral, la jefatura y el alumnado; la administración del
  centro es la única que puede seguir modificándolo.
- **Módulo desactivado.** Si el módulo FCT no está activo en el curso, ningún perfil tiene acceso a él.
