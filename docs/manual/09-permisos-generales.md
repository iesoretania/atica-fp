# Permisos generales

Este capítulo es para **todo el mundo**: resume qué puede hacer cada perfil en la parte general de
ÁTICA FP (acceso, datos personales, administración del centro, empresas, encuestas y plataforma). Cada
módulo tiene después su propia matriz con lo que cada perfil puede hacer en FCT, FP dual y FFE.

La tabla **no está escrita de memoria**: cada versión de la aplicación se prueba entrando con un usuario
de cada perfil y abriendo cada pantalla de la tabla. Si la aplicación cambia un permiso sin que cambie esta
tabla, la prueba falla.

## Cómo leer la tabla

- Cada **columna** es un perfil; cada **fila**, una acción. **✅** significa que el perfil puede hacerlo y
  **—**, que no.
- Un perfil puede **sumar** varios. Una persona que es tutora de grupo y además jefa de departamento tiene
  los permisos de las dos columnas.
- Esta tabla describe lo que **se puede abrir**. Lo que hace cada perfil con ello en cada módulo está en la
  matriz de ese módulo.

## Los perfiles

| Perfil | Quién es | Cómo se obtiene |
| --- | --- | --- |
| **Administración de la plataforma** | Gestiona la instalación completa, todos los centros | Cuenta con el permiso de administrador global ([capítulo 8](08-administrar-la-plataforma.md)) |
| **Administración del centro** | Equipo directivo o quien administre el centro | Asignación en **Gestión del centro → Roles** ([capítulo 4](04-administrar-el-centro.md#administracion-local)) |
| **Responsable económico** | Secretaría del centro | Se elige como secretario o secretaria en los [datos del curso](03-preparar-el-curso-academico.md#los-datos-de-un-curso) |
| **Jefatura de departamento** | Jefatura de una familia profesional | Se asigna en el [departamento](04-administrar-el-centro.md#departamentos) |
| **Coordinación de FP dual** | Responsable de proyectos de FP dual | Se designa como responsable de un proyecto en el módulo de FP dual |
| **Gestión de la FFE** | Responsable de la formación en empresa | Se designa como responsable de un programa formativo en el módulo de FFE |
| **Profesorado sin otro cargo** | Docente sin ningún cargo anterior | Estar en el profesorado del curso |
| **Alumnado** | Estudiante con matrícula | Estar matriculado |

La **tutoría de grupo**, la **tutoría docente** (seguimiento del alumnado en empresa) y la **tutoría
laboral** no aparecen en esta tabla porque no tienen permisos en la parte general: su trabajo está en los
módulos.

## Matriz de permisos

| Puede... | Administración de la plataforma | Administración del centro | Responsable económico | Jefatura de departamento | Coordinación de FP dual | Gestión de la FFE | Profesorado sin otro cargo | Alumnado |
| --- | :-: | :-: | :-: | :-: | :-: | :-: | :-: | :-: |
| **Datos personales** |  |  |  |  |  |  |  |  |
| Ver y cambiar sus datos personales y su contraseña | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Plataforma** |  |  |  |  |  |  |  |  |
| Acceder al apartado de administración | ✅ | — | — | — | — | — | — | — |
| Ver el listado de centros | ✅ | — | — | — | — | — | — | — |
| Editar un centro | ✅ | — | — | — | — | — | — | — |
| Crear un centro | ✅ | — | — | — | — | — | — | — |
| Ver el listado de usuarios | ✅ | — | — | — | — | — | — | — |
| Editar un usuario | ✅ | — | — | — | — | — | — | — |
| **Cursos académicos** |  |  |  |  |  |  |  |  |
| Ver el listado de cursos académicos | ✅ | ✅ | — | — | — | — | — | — |
| Editar un curso académico | ✅ | ✅ | — | — | — | — | — | — |
| Copiar datos de otro curso | ✅ | ✅ | — | — | — | — | — | — |
| **Gestión del centro** |  |  |  |  |  |  |  |  |
| Acceder al apartado de gestión del centro | ✅ | ✅ | ✅ | — | — | — | — | — |
| Ver el listado de profesorado | ✅ | ✅ | — | — | — | — | — | — |
| Editar un docente | ✅ | ✅ | — | — | — | — | — | — |
| Eliminar docentes | ✅ | ✅ | — | — | — | — | — | — |
| Ver el listado de matrículas | ✅ | ✅ | — | — | — | — | — | — |
| Editar una matrícula | ✅ | ✅ | — | — | — | — | — | — |
| Ver el listado de departamentos | ✅ | ✅ | — | — | — | — | — | — |
| Editar un departamento | ✅ | ✅ | — | — | — | — | — | — |
| Ver el listado de enseñanzas | ✅ | ✅ | — | — | — | — | — | — |
| Editar una enseñanza | ✅ | ✅ | — | — | — | — | — | — |
| Ver el listado de niveles | ✅ | ✅ | — | — | — | — | — | — |
| Ver el listado de grupos | ✅ | ✅ | — | — | — | — | — | — |
| Editar un grupo | ✅ | ✅ | — | — | — | — | — | — |
| Ver el listado de materias | ✅ | ✅ | — | — | — | — | — | — |
| Editar una materia | ✅ | ✅ | — | — | — | — | — | — |
| Ver el listado de días no lectivos | ✅ | ✅ | — | — | — | — | — | — |
| Ver los tipos de contacto con empresas | ✅ | ✅ | — | — | — | — | — | — |
| Asignar roles del centro | ✅ | ✅ | — | — | — | — | — | — |
| **Importaciones** |  |  |  |  |  |  |  |  |
| Acceder al apartado de importaciones | ✅ | ✅ | — | — | — | — | — | — |
| Importar profesorado | ✅ | ✅ | — | — | — | — | — | — |
| Importar grupos | ✅ | ✅ | — | — | — | — | — | — |
| Importar alumnado | ✅ | ✅ | — | — | — | — | — | — |
| **Empresas y centros de trabajo** |  |  |  |  |  |  |  |  |
| Ver el listado de empresas | ✅ | ✅ | — | ✅ | ✅ | ✅ | — | — |
| Crear una empresa | ✅ | ✅ | — | ✅ | ✅ | ✅ | — | — |
| Editar una empresa | ✅ | ✅ | — | ✅ | ✅ | ✅ | — | — |
| Eliminar empresas | ✅ | ✅ | — | ✅ | ✅ | ✅ | — | — |
| Ver los centros de trabajo de una empresa | ✅ | ✅ | — | ✅ | ✅ | ✅ | — | — |
| Editar un centro de trabajo | ✅ | ✅ | — | ✅ | ✅ | ✅ | — | — |
| **Encuestas y plantillas** |  |  |  |  |  |  |  |  |
| Ver el listado de encuestas | ✅ | ✅ | — | — | — | — | — | — |
| Editar una encuesta | ✅ | ✅ | — | — | — | — | — | — |
| Ver las preguntas de una encuesta | ✅ | ✅ | — | — | — | — | — | — |
| Ver el listado de plantillas de informe | ✅ | ✅ | — | — | — | — | — | — |
| Editar una plantilla de informe | ✅ | ✅ | — | — | — | — | — | — |
| **Desplazamientos y valoración** |  |  |  |  |  |  |  |  |
| Ver los itinerarios de desplazamiento | ✅ | ✅ | ✅ | — | — | — | — | — |
| Editar un itinerario | ✅ | ✅ | ✅ | — | — | — | — | — |
| Ver las escalas de valoración | ✅ | ✅ | — | — | — | — | — | — |
| Editar una escala de valoración | ✅ | ✅ | — | — | — | — | — | — |

## Matices

- **Administración de la plataforma y del centro.** La administración de la plataforma puede todo lo que
  puede la del centro, en cualquier centro. La del centro solo puede actuar sobre **su** centro.
- **Responsable económico.** Su única tarea en la parte general son los **itinerarios de
  desplazamiento**. Sus permisos sobre los gastos de desplazamiento están en cada módulo.
- **Empresas.** La administración del centro, las jefaturas de departamento, la coordinación de FP dual y la
  gestión de la FFE gestionan el **mismo catálogo de empresas**, que es compartido por todos los centros
  ([capítulo 6](06-empresas-y-centros-de-trabajo.md)). Cualquiera de ellos puede crear, editar y eliminar
  empresas y centros de trabajo; el resto del profesorado, no.
- **Jefatura de departamento.** En la parte general solo gestiona empresas. Su verdadero ámbito son las
  enseñanzas de su departamento **en los módulos**.
- **Profesorado y alumnado.** No tienen permisos de gestión en la parte general. Su trabajo son los
  seguimientos y evaluaciones de los módulos.
