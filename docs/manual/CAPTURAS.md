# Capturas del manual y datos de demostración

Léelo antes de **añadir o regenerar una captura** del manual (`docs/manual/img/`) y antes de **cambiar
los datos de demostración** (`tests/Command/LoadManualDataCommand.php`): esos datos son el escenario de
todas las capturas, y cada dato existe para que el manual muestre algo concreto. Cambiarlos sin más
rompe la relación entre una imagen y su pie.

## Regla para las capturas nuevas

Una captura debe mostrar **el concepto que explica el texto**, no «una pantalla con datos». Antes de
hacerla, anota en el catálogo (más abajo) a qué sección ilustra, qué dato de demostración hace visible el
concepto y qué debe señalar el pie. Si los datos no tienen el caso (una convocatoria bloqueada, un gasto
sin verificar...), **créalo en `LoadManualDataCommand`**; no lo falsees editando la base de datos en un
script. Las ampliaciones deben ser **aditivas**: más convocatorias, empresas o alumnado, no cambios en lo
que ya muestran otras imágenes.

## Cómo está montado

- **Base de datos aparte**: `fp_manual` (en el entorno de test se llama `fp_manual_test`). Nunca la de
  desarrollo ni una real. El script que la recrea se niega a borrar nada si el nombre no contiene
  «manual».
- **Datos**: salen de las fixtures de los tests (la estructura: tres módulos, todos los perfiles, dos
  centros) más una capa de realismo, `app:manual:load-data`, que pone nombres y usuarios creíbles al
  centro y a las personas y añade profesorado y alumnado para que las listas no estén vacías. Las
  fixtures de los tests no cambian.
- **Servidor**: la aplicación en entorno de desarrollo, con el nombre «ÁTICA FP» (no «ÁTICA FP DEV»),
  apuntando a esa base de datos.
- **Capturas**: scripts de Node con [Playwright](https://playwright.dev) en `scripts/screenshots/`,
  uno por capítulo, sobre una librería común (`lib.mjs`). Playwright **no es dependencia del proyecto**
  (no se toca `package.json` ni el rango de versiones de Node): se instala fuera del repositorio.

## Procedimiento

1. **Instala Playwright fuera del repositorio** (una sola vez), con una versión cuyo Chromium ya tengas en
   caché, o deja que descargue uno:

   ```bash
   mkdir -p ~/atica-fp-shots && cd ~/atica-fp-shots
   npm init -y && npm i playwright@1.61.0 pixelmatch@5.3.0 pngjs@7.0.0
   ```

   `pixelmatch` y `pngjs` solo sirven para que una captura no sustituya a la imagen existente si la diferencia
   es ruido (ver «Reglas aprendidas»); sin ellos todo funciona, pero git verá cambios en imágenes que no han
   cambiado.

2. **Recrea los datos** (cada vez que quieras empezar de cero: los scripts que modifican datos dejan la
   base de datos distinta de la inicial):

   ```bash
   make docs-data
   ```

   La conexión sale de `MANUAL_DATABASE_URL`, o de `DATABASE_URL` de `.env.test.local` cambiando el nombre
   de la base de datos por `fp_manual`.

3. **Sirve la aplicación** (en otra terminal, déjala abierta):

   ```bash
   make docs-app        # http://127.0.0.1:8744
   ```

4. **Ejecuta el script del capítulo** desde la raíz del repositorio:

   ```bash
   PLAYWRIGHT_DIR=~/atica-fp-shots node scripts/screenshots/capture-primeros-pasos.mjs
   ```

   Variables opcionales: `SHOTS_BASE_URL` (por defecto `http://127.0.0.1:8744`), `SHOTS_OUT_DIR` (por
   defecto `docs/manual/img`) y `MANUAL_PORT` para el servidor.

5. **Mira cada PNG antes de confirmarlo.** Un desplegable cortado, un componente a medio pintar o una barra
   lateral descolocada son fáciles de pasar por alto.

### Reglas aprendidas

- Sin conexión a internet la tipografía (Google Fonts) no carga; la librería aborta esa petición a los
  4 s para que la página no se quede esperando. Con conexión, las capturas llevan la tipografía real.
- `lib.mjs` oculta la barra de depuración de Symfony y sube al principio de la página antes de cada captura
  (`prepare`): una captura de página completa tras un scroll descoloca los elementos fijos.
- Tras pulsar algo que recarga una parte de la página, espera al nuevo estado (`waitForSelector` sobre un
  elemento visible), no solo a `networkidle`.
- **«Hoy» está fijado en dos sitios, y deben coincidir**: los **datos** se cargan con `DEMO_TODAY=2026-10-31`
  (lo define `scripts/manual.sh data`; las fixtures de los tests no lo usan y siguen con la fecha real), de modo que
  las jornadas, reuniones, visitas y gastos caen siempre en las mismas fechas, y el **reloj del navegador** está en
  esa misma fecha (`fixedNow` en `lib.mjs`; se cambia con `SHOTS_NOW`). Además, el puntero se aparta antes de cada
  captura (si no, resalta la tarjeta que quede debajo del botón de entrada) y se espera a la tipografía.
- **El reloj del servidor PHP no se puede fijar**, y esto es lo que sigue dependiendo del día real:
  - los **valores por defecto de los formularios nuevos** (visita, contacto, gasto, informe, añadir jornadas: fecha y
    hora actuales) y la columna «Últ. conexión» del listado de usuarios de la plataforma (la hora de los accesos de la
    propia captura): se sustituyen por la fecha de referencia con `fixDateTime: true` y `fixLastAccess: true` en la
    captura (ver `lib.mjs`); **toda captura nueva de un formulario de alta debe llevar `fixDateTime: true`**;
  - el **año del pie de página** (cambiará cada enero) y las **jornadas que el servidor considera pasadas o futuras**
    (a partir del 28/11/2026 todas son pasadas), que no se pueden sustituir;
  - el botón **«Hoy»** de los calendarios de seguimiento (solo aparece si hay una jornada en el día real, es decir,
    entre el 31/10/2026 y el 28/11/2026 en un día laborable).

  Si una imagen cambia sin motivo al regenerarla, mira primero si es una de estas.
- **Una captura solo reemplaza a la imagen existente si difiere de ella en más de 100 píxeles** (según
  `pixelmatch`) o cambia de tamaño: Chromium suaviza el texto de forma ligeramente distinta en cada ejecución
  y, sin esa tolerancia, git mostraría decenas de imágenes modificadas sin motivo. El script lo indica con «(sin
  cambios)». Para forzar la escritura (por ejemplo, tras cambiar un único carácter de una pantalla), usa
  `SHOTS_FORCE=1`. El ruido de Chromium aparece a veces al lanzar el navegador (por ejemplo, cuando el equipo está
  ocupado con otra cosa), así que **no ejecutes tests ni otras tareas pesadas mientras generas capturas**.
- Los scripts de solo lectura pueden ejecutarse en cualquier orden; los que modifican datos se anotan en
  la columna «¿Modifica datos?» del catálogo y se ejecutan al final.

## Usuarios de demostración

Todos con la contraseña `demo1234`. Centro **I.E.S. Ada Lovelace** (código `29700456`), curso 2026-2027.
La fuente es `LoadManualDataCommand::PROFILES`; `scripts/screenshots/lib.mjs` (`accounts`) debe coincidir.

| Perfil | Clave en `lib.mjs` | Usuario | Persona |
| --- | --- | --- | --- |
| Administración de la plataforma | `plataforma` | `admin` | Administrador global |
| Administración del centro | `administracion` | `avalverde` | Antonio Valverde Ruiz |
| Dirección | `direccion` | `lortega` | Lucía Ortega Molina |
| Responsable económico | `economico` | `ireyes` | Inmaculada Reyes Pardo |
| Jefatura de departamento (Informática) | `jefatura` | `rcabrera` | Ramón Cabrera Soler |
| Tutoría docente y de grupo (2º DAM A) | `tutoria` | `mjimenez` | Marta Jiménez Aranda |
| Profesorado | `profesorado` | `jbarrios` | Julián Barrios Cortés |
| Profesorado que solo da clase en 2º DAM A (evalúa en FP dual y FFE) | `docenteGrupo` | `jcortes` | Julia Cortés Ramos |
| Coordinación de FP dual | `coordinacion` | `esalas` | Elena Salas Quintana |
| Gestión de la FFE | `gestorFfe` | `therrero` | Tomás Herrero Lara |
| Tutoría laboral (Tecnologías del Sur) | `tutoriaLaboral` | `snavarro` | Sergio Navarro Gil |
| Tutoría laboral (Consultoría del Norte) | `tutoriaLaboral2` | `pduran` | Patricia Durán Vela |
| Alumnado con acuerdo de FCT | `alumnoFct` | `dfernandez` | Daniel Fernández Ortiz |
| Alumnado con acuerdo de FP dual | `alumnoDual` | `apastor` | Alejandro Pastor Vega |
| Alumnado con estancia de FFE | `alumnoFfe` | `aibanez` | Adrián Ibáñez Mora |

El resto del profesorado (9 docentes) y del alumnado (34 estudiantes, 14 en 2º DAM A y 20 en 1º DAM A) son
datos de relleno sin acuerdos, útiles para mostrar listas con contenido y para crear acuerdos nuevos.

## Catálogo de capturas

| Script | Imágenes (`docs/manual/img/`) | Capítulo | ¿Modifica datos? |
| --- | --- | --- | --- |
| `capture-primeros-pasos.mjs` | `primeros-pasos-acceso`, `-inicio`, `-datos-personales` | `02` | No |
| `capture-parte-general.mjs` | `primeros-pasos-recuperar-contrasena`, `-seleccion-centro` | `02` | No |
| | `preparar-curso-*` (listado, formulario, copiar, inicio del centro) | `03` | No |
| | `centro-*` (profesorado, matrículas, departamentos, enseñanzas, niveles, grupos, materias, resultados, días no lectivos, tipos de contacto, roles, itinerarios, escalas y las fichas de cada uno; `centro-economico-inicio` y `centro-itinerarios-economico` con el perfil de responsable económico) | `04` | No |
| | `importaciones-*` (inicio, profesorado, grupos, alumnado, usuarios PASEN, días no lectivos) | `05` | No |
| | `empresas-*` (listado, ficha, sedes) | `06` | No |
| | `encuestas-*` y `plantillas-*` (listados, ficha de encuesta, preguntas, ficha de pregunta, ficha de plantilla) | `07` | No |
| | `plataforma-*` (centros, ficha de centro, usuarios, ficha de usuario) | `08` | No |
| `capture-fct.mjs` | `fct-*` de la administración (`fct-inicio-gestion`, convocatorias, actividades, acuerdos, calendario, seguimiento, informes, visitas y gastos), de la jefatura, la tutoría docente (`fct-tutoria-*`, `fct-jornada-*`), el alumnado (`fct-alumnado-*`), la tutoría laboral (`fct-laboral-*`) y el responsable económico (`fct-economico-*`) | `10` a `16` | No |
| `capture-dual.mjs` | `dual-*` de la administración (proyectos, alumnado adherido, actividades, concreciones, empresas, convenios, calendario, seguimiento, valoración, resumen, contactos, gastos, reuniones de tutorización e informes), de la coordinación y la jefatura, la tutoría docente y de grupo (`dual-tutoria-*`, `dual-jornada-*`, `dual-evaluacion-*`), el profesorado del grupo (`dual-docente-*`), el alumnado (`dual-alumnado-*`), la tutoría laboral (`dual-laboral-*`) y el responsable económico (`dual-economico-*`) | `20` a `27` | No |

| `capture-ffe.mjs` | `ffe-*` de la administración (planes, cursos, grupos, actividades, empresas, alumnado, estancias, calendarios, resumen de evaluación, seguimiento, contactos, gastos e informes), la gestión de la FFE y la jefatura, la tutoría docente y de grupo (`ffe-tutoria-*`, `ffe-jornada-*`, `ffe-valoracion-*`), el responsable de seguimiento, el profesorado del grupo (`ffe-docente-*`), el alumnado (`ffe-alumnado-*`), la tutoría laboral (`ffe-laboral-*`) y el responsable económico (`ffe-economico-*`) | `30` a `37` | No |

Cada script nuevo se anota aquí al crearlo.

## Qué falta en los datos (se amplía al redactar cada módulo)

Ampliaciones ya hechas (`LoadManualDataCommand::addFfe`, fase 5, todas aditivas): Julián Barrios y Tomás Herrero dan
clase en 2º DAM A (los formularios del grupo y de la estancia solo ofrecen al profesorado del grupo; con ello salen
también en las listas de docentes del módulo de FP dual y en las materias); la empresa de la estancia de Sara Gallego
entra en el plan con su convenio (FFE-002); el plan usa las tres encuestas del centro; las jornadas hasta una semana antes
de «hoy» están confirmadas y Sara tiene una falta justificada y otra injustificada.

Los datos tienen una sola enseñanza (CFGS DAM), una convocatoria de FCT, un proyecto de FP dual y un
programa de FFE. Es suficiente para el recorrido de cada perfil, pero cuando un capítulo necesite variedad
(varias convocatorias, una bloqueada, un gasto sin verificar, un proyecto cerrado...) se añade al comando y
se registra en este fichero, en la fase del módulo correspondiente (ver `PLAN.md`).
