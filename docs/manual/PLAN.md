# Plan de redacción del manual de ÁTICA FP

Documento de trabajo: se elimina (o se archiva) cuando el manual esté completo. No se publica en la web ni
en el PDF.

## 1. Objetivo y criterios

Un único manual, dirigido a quien usa la aplicación (no a quien la programa), que se pueda leer por
**perfil** sin recorrerlo entero. Sigue la estructura del manual de ÁTICA Calidad: capítulos numerados,
una portada «Quién es quién» con enlaces por perfil, matriz de permisos, anexo de resolución de problemas
y glosario.

Criterios que se mantienen en todos los capítulos:

- **General frente a módulo.** Lo que no depende de un módulo (acceso, administración del centro,
  importaciones, empresas, encuestas, plataforma) va solo en la parte general. Cada módulo (FCT, FP dual,
  FFE) cuenta solo lo suyo y remite a la parte general para lo común. No se repite contenido: se enlaza.
- **Por perfil dentro de cada módulo.** Cada capítulo de módulo es «lo que hace este perfil, en este
  módulo», con el recorrido real de pantallas. Un perfil que no interviene en un módulo no tiene capítulo.
- **Matriz de permisos en cada módulo** (y una general), generada y comprobada contra el código, no
  escrita de memoria (ver apartado 4).
- **Capturas donde aclaran algo**, siempre con un pie que dice qué dato de la demostración se ve y por
  qué (ver apartado 5). Sin capturas decorativas.
- Redacción en español, de «tú», sin jerga técnica salvo en los capítulos de instalación y plataforma.
- El manual describe la versión actual (7.x); no documenta versiones anteriores.

## 2. Perfiles

Perfiles reales, tal como los distingue la aplicación (voters y menús):

| Perfil | Quién es |
| --- | --- |
| Administración de la plataforma | Cuenta con acceso global (`ROLE_ADMIN`): gestiona organizaciones y usuarios |
| Administración del centro | Administrador local de la organización (equipo directivo) |
| Responsable económico | Consulta y gestiona los gastos de desplazamiento del centro |
| Jefatura de departamento | Gestiona la formación de las enseñanzas de su departamento |
| Coordinación de FP dual | Persona responsable de uno o más proyectos de FP dual |
| Gestión de la FFE | Persona que gestiona los programas formativos de la FFE |
| Tutoría docente | Profesorado responsable de seguimiento de los acuerdos o estancias |
| Tutoría de grupo | Tutor o tutora de un grupo con alumnado en el módulo |
| Profesorado | Profesorado que imparte docencia en el grupo (evalúa en FP dual y FFE) |
| Alumnado | Alumnado con acuerdo o estancia |
| Tutoría laboral | Persona de la empresa que tutoriza al alumnado |

## 3. Estructura de capítulos

Ficheros `NN-*.md` en `docs/manual/`. La web los agrupa en secciones de `nav`; el PDF los concatena por
número. Rangos fijos para poder añadir capítulos sin renumerar: 01-09 general, 10-19 FCT, 20-29 FP dual,
30-39 FFE, 90 en adelante anexos.

### Introducción

| Fichero | Contenido |
| --- | --- |
| `index.md` | Qué es ÁTICA FP, los tres módulos, quién es quién (enlaces por perfil), cómo usar el manual |

### Parte A. Funcionalidad general

| Fichero | Perfiles | Contenido |
| --- | --- | --- |
| `01-instalacion-y-puesta-en-marcha.md` | Plataforma | Requisitos, Docker, variables, `app:admin`, `app:organization`, tareas programadas (`app:cron`), copias (`app:backup`, `app:backup-restore`), migraciones seguras (`app:safe-migrate`), actualizaciones entre versiones |
| `02-primeros-pasos.md` | Todos | Acceso, selección de organización, menú, datos personales y contraseña, recuperación de contraseña |
| `03-preparar-el-curso-academico.md` | Administración del centro | Cursos académicos y copia del curso, módulos activos, orden recomendado de preparación |
| `04-administrar-el-centro.md` | Administración del centro | Profesorado, alumnado y matrículas, departamentos, enseñanzas, niveles, grupos, materias, competencias, resultados de aprendizaje y criterios, días no lectivos, métodos de contacto, perfiles de usuario, rutas de desplazamiento, escalas de valoración |
| `05-importaciones.md` | Administración del centro | Importación desde Séneca y CSV: profesorado, grupos, materias, criterios, alumnado y claves, departamentos, días no lectivos; cómo interpretar el resultado |
| `06-empresas-y-centros-de-trabajo.md` | Administración del centro, jefatura | Empresas, centros de trabajo, sus actividades y personas de contacto |
| `07-encuestas-y-plantillas-de-informe.md` | Administración del centro | Crear encuestas y preguntas, plantillas de informe y su uso en los módulos |
| `08-administrar-la-plataforma.md` | Plataforma | Organizaciones, usuarios, suplantación, módulos disponibles |
| `09-permisos-generales.md` | Todos | **Matriz de permisos general** |

### Parte B. FCT (Formación en centros de trabajo)

| Fichero | Perfil | Contenido |
| --- | --- | --- |
| `10-fct-vision-general.md` | Todos | Qué es el módulo, ciclo de una FCT de principio a fin, vocabulario |
| `11-fct-gestion.md` | Administración del centro y jefatura de departamento | Convocatorias, actividades (alta, importación, copia), acuerdos y su alumnado, calendario, informes y encuestas del departamento |
| `12-fct-tutoria-docente-y-de-grupo.md` | Tutoría docente, tutoría de grupo | Seguimiento diario, visitas, gastos de desplazamiento, informe final, encuestas de centro y de alumnado |
| `13-fct-alumnado.md` | Alumnado | Registro diario, informe final, encuesta |
| `14-fct-tutoria-laboral.md` | Tutoría laboral | Asistencia y valoración, encuesta de empresa |
| `15-fct-responsable-economico.md` | Responsable económico | Gastos de desplazamiento: consulta, informe, verificación |
| `16-fct-permisos.md` | Todos | **Matriz de permisos de FCT** |

### Parte C. FP dual

| Fichero | Perfil | Contenido |
| --- | --- | --- |
| `20-dual-vision-general.md` | Todos | Qué es el módulo, ciclo de un proyecto, vocabulario |
| `21-dual-gestion.md` | Administración, coordinación y jefatura | Proyectos, grupos y alumnado, programa formativo, actividades y concreciones, acuerdos, calendarios, informes |
| `22-dual-tutoria-docente-y-de-grupo.md` | Tutoría docente y de grupo | Seguimiento diario, reuniones, contactos, gastos, encuestas |
| `23-dual-profesorado-y-evaluacion.md` | Profesorado | Evaluación por actividad y resultado de aprendizaje, resumen de resultados |
| `24-dual-alumnado.md` | Alumnado | Registro diario, actividades realizadas, encuesta |
| `25-dual-tutoria-laboral.md` | Tutoría laboral | Asistencia, valoración, encuesta de empresa |
| `26-dual-responsable-economico.md` | Responsable económico | Gastos de desplazamiento |
| `27-dual-permisos.md` | Todos | **Matriz de permisos de FP dual** |

### Parte D. FFE (Formación en empresa)

| Fichero | Perfil | Contenido |
| --- | --- | --- |
| `30-ffe-vision-general.md` | Todos | Qué es el módulo, ciclo de un programa formativo, vocabulario |
| `31-ffe-gestion.md` | Administración, gestión de la FFE y jefatura | Programas formativos, grupos, alumnado, empresas del programa, estancias (alta por lotes, copia de calendario), actividades, informes |
| `32-ffe-tutoria-docente-y-de-grupo.md` | Tutoría docente y de grupo | Seguimiento, visitas, gastos, encuestas, calificación |
| `33-ffe-profesorado-y-evaluacion.md` | Profesorado | Calificación y resumen de evaluación |
| `34-ffe-alumnado.md` | Alumnado | Registro de jornadas, encuesta |
| `35-ffe-tutoria-laboral.md` | Tutoría laboral | Asistencia, valoración, encuesta de empresa |
| `36-ffe-responsable-economico.md` | Responsable económico | Gastos de desplazamiento |
| `37-ffe-permisos.md` | Todos | **Matriz de permisos de la FFE** |

### Anexos

| Fichero | Contenido |
| --- | --- |
| `90-resolucion-de-problemas.md` | Síntomas frecuentes y su causa (sin acceso a un módulo, alumnado que no aparece, importación con conflictos...) |
| `91-glosario.md` | FCT, FP dual, FFE, acuerdo, estancia, convocatoria, concreción de actividad, tutoría docente y laboral... |

Cada capítulo de perfil sigue la misma plantilla, para que el manual sea predecible:

1. **Qué puedes hacer** (resumen en una lista corta, con enlace a la matriz).
2. **Dónde está** (entrada del menú y cuándo aparece).
3. **Tareas**, en el orden en que se presentan durante el curso, una por sección.
4. **Qué no puedes hacer y a quién pedirlo** (límites del perfil; evita consultas innecesarias).

## 4. Matrices de permisos

Una por módulo y una general. Formato igual que `11-permisos-de-un-vistazo.md` de ÁTICA Calidad:
filas = acciones, columnas = perfiles, celdas `✅` / `—`, con notas al pie para los matices (por ejemplo,
«solo de sus propios acuerdos» o «solo del departamento que dirige»).

Para que no queden desfasadas se construyen en dos pasos:

1. **Borrador desde el código**: las acciones salen de las entradas de menú y de los voters del módulo
   (`src/Security/*Module/*Voter.php`), que ya tienen su batería de tests por perfil.
2. **Comprobación automática** (se hace en la fase 1): un script de pruebas inicia sesión con cada perfil
   de los datos de demostración y recorre las URL de cada acción, anotando permitido/denegado. La matriz
   publicada debe coincidir con ese resultado; si el código cambia, la comprobación avisa. Así la matriz
   sirve también de test de regresión de permisos.

Los matices que no son «sí/no» (alcance por departamento, por proyecto, por grupo) se explican bajo la
tabla, no dentro de ella.

## 5. Capturas

Mecanismo igual al de ÁTICA Calidad: scripts de Node con [Playwright](https://playwright.dev), uno por
capítulo, que recorren la aplicación con una cuenta de cada perfil y guardan PNG en `docs/manual/img/`
con el prefijo del capítulo (`fct-gestion-convocatorias.png`).

- Playwright **no** es dependencia del proyecto: no se toca `package.json` ni el rango de versiones de
  Node. Se instala en un directorio temporal, fuera del repositorio.
- Se ejecutan contra un servidor local con una **base de datos desechable** sembrada con los datos de
  demostración, nunca contra la real.
- `scripts/router-shots.php` y un `skills/screenshots.md` equivalente documentan el procedimiento y el
  catálogo de datos (qué dato de demostración sirve para qué captura).
- Una captura por cada pantalla que el texto describe y una por cada estado relevante (lista vacía frente
  a llena, formulario con error, confirmación de borrado), no más. Orden de magnitud: 10-15 por capítulo
  de perfil, es decir, unas 200 en total.
- Cada imagen se revisa a ojo antes de confirmarla y el pie nombra lo que se ve.

## 6. Fases y orden de trabajo

| Fase | Entrega | Criterio de hecho |
| --- | --- | --- |
| 0 | **Mecanismo de generación** (hecho): `Makefile`, MkDocs, pandoc, estilos, CI | `make docs` genera PDF y web |
| 1 | **Datos de demostración y capturas**: dataset realista (nombres, empresas, calendario), cuentas por perfil, scripts base y runbook; comprobación automática de permisos | Se pueden regenerar las capturas de un capítulo con un comando; la comprobación de permisos pasa |
| 2 | Parte A (general), capítulos 01-09 | Revisados y con capturas |
| 3 | FCT (10-16): es el módulo más simple y fija la plantilla de los demás | Revisado por una persona que use el módulo |
| 4 | FP dual (20-27) | Idem |
| 5 | FFE (30-37) | Idem |
| 6 | Anexos, introducción definitiva, revisión transversal de enlaces y de coherencia terminológica | `make docs` sin avisos; enlaces comprobados |
| 7 | Publicación: web y PDF adjuntos a cada versión | Según se decida (ver preguntas) |

Cada fase termina con un commit propio. El manual se redacta contra la rama `v7`; los cambios de
pantallas posteriores actualizan el capítulo y sus capturas en el mismo commit que el cambio.

## 7. Control de calidad

- Cada afirmación sobre permisos se contrasta con la matriz comprobada.
- Cada capítulo lo recorre quien lo escribe en la aplicación, siguiendo sus propios pasos con la cuenta
  del perfil, antes de darlo por bueno.
- Enlaces internos y de anclas: `make docs-web` avisa de los rotos.
- Terminología única (`91-glosario.md`): «estancia» y «acuerdo» no se mezclan, FCT/FP dual/FFE se
  escriben igual en todo el manual.
