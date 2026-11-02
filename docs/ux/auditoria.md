# Auditoría de UX y propuesta de rediseño (rama `ux-redesign`)

Estado de partida: rama `symfony-7.4` (AssetMapper, Bootstrap 4.6 con tema Yeti, jQuery, Select2, Quill).
Referencia visual y técnica: `atica-calidad` (Tailwind 4, Stimulus, Live Components, Tom Select, UX Icons, Quill).

## 1. Inventario

| Área | Dato |
|---|---|
| Plantillas Twig | 291 (wlt 68, itp 66, organization 65, wpt 51, admin 15, security 7, company 6) |
| Layout | `layout.html.twig` (navbar superior oscura fija + migas + `h1.well`), `macros*.twig`, menú en `layout/` |
| Temas de formulario | `bootstrap_4_horizontal_layout` (global), `bootstrap_4_layout` (2 plantillas), `bootstrap_3_layout` (1), 18 temas locales `_self`, `@TetranzSelect2Entity` |
| Iconos | Font Awesome 6 (32 iconos distintos; mezcla de `fa` y `fas`) |
| JavaScript | 17 módulos de página + jQuery/Bootstrap JS/Select2/Quill; 11 plantillas con `<script>` en línea |
| Estilos en línea | 248 atributos `style="..."` |

## 2. Inconsistencias detectadas

1. **Botones**: mezcla de `btn-default` (Bootstrap 3, ya inexistente en BS4), `btn-info`, `btn-success`, `btn-primary`, `btn-link`; `btn-sm` en 61 sitios y sin tamaño en otros; clases de botón definidas también en `src/Form` (`btn btn-success`) y `btn-sm-block` que no existe en Bootstrap.
2. **Formularios**: tres temas distintos (BS3, BS4 vertical, BS4 horizontal) y 18 temas locales; etiquetas y errores se presentan de forma diferente según la pantalla. `login_organization` sigue con el tema de Bootstrap 3.
3. **Tablas**: 8 combinaciones de clases (`table-bordered table-hover table-striped`, `table-sm`, `table-condensed` (BS3), `survey`, `data`, `calendar`, `big`…). Sin versión móvil.
4. **Avisos**: 81 de 98 son `alert-warning`, incluso para información o estados vacíos; no hay jerarquía semántica (info/éxito/aviso/error).
5. **Contenedores**: `card`, `well` (BS3), `panel-body` (BS3) conviven; títulos como `h1.well`.
6. **Iconos**: `fa` y `fas` mezclados; cuadrados de selección con `fa-square`/`fa-check-square` en lugar de controles reales.
7. **Navegación**: barra superior que se desborda con muchos módulos, migas ocultas en móvil (`d-none d-lg-flex`), sin menú lateral ni búsqueda rápida.
8. **Accesibilidad/estados**: el foco, los estados vacíos y los estados de carga no están normalizados; hay `hidden-xs hidden-sm` (BS3) en las migas.
9. **JS**: lógica repartida en 17 módulos de página con jQuery, más 11 scripts en línea; Select2 para selects y `select2entity` para entidades remotas.
10. **Informes PDF (mPDF)** usan plantillas aparte (`layout/base_report`): **no se tocan** (no se ven afectados por Tailwind).

## 3. Sistema de diseño propuesto (misma base técnica que atica-calidad, identidad visual propia)

- **Tailwind 4** con `symfonycasts/tailwind-bundle` (binario autónomo, sin Node), `@tailwindcss/typography`.
- **Tipografía**: Inter servida en local (sin Google Fonts, por RGPD), como en atica-calidad.
- **Paleta propia «Petróleo»** (`brand-*`, azul verdoso ~190°) para la acción principal, deliberadamente distinta de ÁTICA Calidad (salvia), Gestconv Plus (lavanda) y Nexo FP (ciruela). Neutros, avisos y peligro con los valores estándar de Tailwind; semánticos para éxito/info/aviso/peligro.
- **Layout**: barra lateral colapsable (módulos) + cabecera con centro/usuario + migas siempre visibles + paleta de comandos opcional.
- **Componentes Twig anónimos** (`templates/components/`): `Button`, `Alert`, `Card`, `PageHeader`, `Table` (con modo tarjetas en móvil), `Badge`, `EmptyState`, `Modal`, `Pagination`, `FormRow`.
- **Formularios**: un único tema Tailwind (`form/tailwind_layout.html.twig`) que sustituye a los 3 de Bootstrap y a los 18 locales.
- **Iconos**: `symfony/ux-icons` (SVG en línea, p. ej. Heroicons/Lucide) en lugar de Font Awesome.
- **Interacción** (sustitución de jQuery):
  - Select2 y `select2entity` → `symfony/ux-autocomplete` (Tom Select).
  - Quill → controlador Stimulus `rich_editor`.
  - Filtros de listado (`list.js`), selects dependientes, búsqueda → Live Components.
  - Modales, desplegables, popovers y colapsables → Stimulus + `<dialog>`/Tailwind (sin Bootstrap JS).
  - Scripts de calendarios/seguimiento → controladores Stimulus.

## 4. Estrategia por fases

1. **Cimientos** (sin cambiar pantallas aún): instalar Tailwind, Stimulus, UX Icons, Live Components, Tom Select; crear tokens y componentes. Convivencia temporal con Bootstrap bajo un layout nuevo (`layout_v2`) para poder migrar por módulos.
2. **Prototipo de un módulo completo** (layout, listado, formulario, ficha) para validar el estilo.
3. **Migración por módulos**: Organización → Empresas → FCT (`wpt`) → FP dual (`wlt`) → FFEOE (`itp`) → Admin/Seguridad.
4. **Retirada**: eliminar Bootstrap, jQuery, Select2, Font Awesome, `select2entity-bundle` local y el tema Yeti.
5. **Cierre**: rehacer capturas del manual (`docs/manual`), actualizar README.

## 4b. Live Components (piloto en FCT)

Piloto con dos pantallas: el listado de acuerdos (`Wpt:AgreementList`) y el formulario de visitas (`Wpt:VisitForm`).

- **Listados** (`src/Twig/Components/AbstractListComponent.php` + `templates/ui/live_list.html.twig`): filtro (`q`) y página
  (`page`) son estado del componente. Para añadir otro listado: una clase que extienda `AbstractListComponent`
  (`authorize()` y `createQueryBuilder()`), una plantilla en `templates/components/` que haga `{% embed 'ui/live_list.html.twig' %}`
  con sus bloques, y una página que la incluya. Dentro de un `embed` los bloques **no** heredan `trans_default_domain`.
- **Historial del navegador** (`assets/controllers/live_history_controller.js`, mismo patrón que el árbol documental de ÁTICA
  Calidad): `url: true` de Live Components solo hace `replaceState`, así que el estado se guarda con `pushState` al cambiar de
  página, de orden o de filtro (escribir en el buscador reemplaza la entrada) y `popstate` llama a la acción `syncFromUrl`.
  Resultado: volver desde una ficha o con «Atrás» deja el listado como estaba
  (`?q=...&page=...&sort=...&dir=...&f_filtro=...`). Las páginas lo incluyen con `ui/_live_list.html.twig` (usa la función
  Twig `live_list_state()` para leer ese estado de la URL).
- **Filtros propios, orden y selección**: un listado define `filterDefinitions()` (select o fecha; los valores se validan
  siempre contra la definición con `filter('clave')`) y `sortableColumns()` (clave => expresión DQL o lista). La plantilla usa
  las macros de `ui/live_macros.html.twig` para las cabeceras ordenables. Se muestran «chips» de los filtros activos, el
  contador («Mostrando 1–20 de 27») y, sin resultados, un botón «Quitar filtros». Las acciones por lote (borrar, copiar) van en
  el bloque `selection_actions`: aparecen en una barra con el número de filas marcadas.
- **Filas clicables**: solo en listados de consulta (sin casillas ni botones en la fila). Con casillas y botones, la fila no es
  un enlace: se abre con el enlace del nombre (así un clic al lado de un botón no abre la ficha sin querer). No se abre la
  ficha si hay texto seleccionado.
- **Formularios** (`ComponentWithFormTrait`): el componente solo repinta los campos dependientes mientras se edita; el
  guardado sigue siendo un envío normal al controlador. Una entidad nueva no se puede hidratar por id, así que el componente
  guarda el docente y los valores del formulario y construye la entidad en cada petición. La propiedad no puede llamarse como el
  formulario (`visit`). Los campos que pasan de `<select>` a casillas necesitan normalizar su valor a lista.
- **Disposición de formularios** (sin dejar el componente Form): `ui/form_layout.html.twig` ofrece la macro `grid(form, filas,
  opciones)`: cada fila es una lista de campos que se reparten el ancho a partes iguales (de 1 a 6 columnas; `null` reserva
  una columna). Los campos que no se mencionan se añaden al final a todo el ancho. Mantiene validación, CSRF y los bloques
  personalizados del tema.
- **Anchos proporcionales**: en `grid`, `campo:peso` reparte el ancho de la fila (`['code:1', 'name:4']`: código corto y nombre
  largo).
- **Seguridad**: cada actualización llega a `/_components/...` como una petición nueva, así que los permisos se comprueban en
  cada una (`authorize()`/`#[PostHydrate]`), no solo en el controlador de la página.
- **Campos con DOM propio dentro de un Live Component** (selectores remotos Tom Select, editor Quill): su fila debe llevar
  `data-live-ignore` (`row_attr`), para que el repintado no destruya el DOM que crea JavaScript. El valor se envía igualmente
  con el `<form>` normal.
- **Formularios con zona de peligro** (`fl.danger_zone`): tarjeta roja bajo el formulario, fuera del `<form>` principal, que
  envía el id a la operación de borrado del listado (que pide confirmación).

### FFEOE (`Itp`)

Todos los listados de FFEOE son Live Components (`src/Twig/Components/Itp/`): programas formativos, actividades, empresas,
estudiantes, estancias (por estudiante y gestión del ciclo), contactos (filtro de forma y fechas), desplazamientos (detalle y
resumen), seguimiento, valoración y evaluación, encuestas (alumnado, empresas, centro) e informes de encuestas. Las tablas
de «Grupos» y «Ciclos» del plan son resúmenes sobre datos ya calculados (no hay filtro, orden ni paginación que ofrecer) y
siguen siendo plantillas estáticas.

Formularios con campos dependientes (`Itp:ContactForm`, `Itp:StayForm` —individual, edición desde la gestión y alta por lote—
e `Itp:ActivityForm`): ver sus clases para las reglas de cascada. Las selecciones que dejan de ser válidas se vacían antes del
envío del componente (nunca aparece «la opción seleccionada no es válida»). Pruebas: `Itp*ComponentTest` y `ItpListComponentsTest`
(que incluye la repetición de la petición desde otro centro, que debe dar 403).

### FP dual (`Wlt`)

Módulo completo en el diseño nuevo (68 plantillas), con los listados como Live Components (`src/Twig/Components/Wlt/`):
proyectos, actividades, concreciones, empresas del proyecto, convenios (filtro por grupo), contactos (forma y fechas),
reuniones (proyecto y fechas), seguimiento, valoración, resultados del alumnado, desplazamientos (detalle y resumen),
encuestas (alumnado, empresas, centro) e informes (proyectos con enlace al informe, y contactos por docente y por centro).

Formularios Live con campos dependientes: `Wlt:MeetingForm` (autor → proyecto → alumnado y docentes), `Wlt:ContactForm`
(fecha y centro → proyectos → alumnado) y `Wlt:ContactEducationalTutorReportForm` (proyectos → centro de trabajo). El
convenio mantiene `dependent-fields` (como FCT). Los docentes elegibles se calculan en servicios compartidos por el
controlador y el componente (`MeetingTeacherProvider`, `ContactTeacherProvider`, `ContactReportTeacherProvider`), de modo que
cada actualización repite exactamente las comprobaciones del controlador (ver `hallazgos-seguridad.md`).

Cambios de tema que se aplican a todos los módulos:

- **Ayudas**: si un campo no declara `help`, el tema muestra la traducción `<etiqueta>.help` del dominio del formulario (la
  misma convención que las explicaciones de las opciones de una lista de tarjetas). Basta con añadir traducciones.
- **Desplegables**: todos los `<select>` de formulario son Tom Select (`choice-select`), con búsqueda; dentro de un Live
  Component se reconstruyen solos cuando el campo se repinta.
- **Listas de casillas agrupadas** (`group_by`): un recuadro por grupo con su «Seleccionar todos».
- **Valoraciones**: `attr: {'data-layout': 'row'}` en un campo de tarjetas las coloca en una fila si caben y en vertical si no
  (`.choice-row-N` en `app.css`); el título es la valoración y la ayuda su descripción.
- **Calendarios**: `ui/calendar.html.twig` admite `hours_divisor` (FP dual guarda horas, FCT y FFEOE centésimas).
- **Barra lateral**: separadores sobre «Empresas colaboradoras» y «Gestión del centro», «Mi perfil» y selector de centro con
  chevron.
- **Calendarios de 5 días**: `ui/calendar.html.twig` admite `five_day_week`; muestra solo de lunes a viernes salvo que haya
  jornadas en sábado o domingo (FFEOE lo activa según el tipo de plantilla semanal del plan de formación).

### Organización, empresas, administración y acceso

- **Organización** (`src/Twig/Components/Organization/`): listados Live de tipos de contacto, departamentos, niveles, grupos,
  días no lectivos, enseñanzas, módulos, profesorado, matrículas, escalas, plantillas de informe, encuestas, cursos académicos
  e itinerarios, y los listados que cuelgan de otro elemento (valores de una escala, preguntas de una encuesta, competencias,
  resultados de aprendizaje y criterios). `AbstractAcademicYearList` y `AbstractOrganizationList` fijan la autorización común;
  los que llevan la entidad padre en las propiedades (escala, encuesta, enseñanza, módulo, resultado) comprueban en cada
  renderizado que sea del centro actual. Formularios con tarjeta, ayudas y zona de peligro; confirmaciones de borrado con
  `ui/delete.html.twig` (admite `prompt_key`, `confirm_key`, `items_name` y el bloque `delete_hidden`).
- **Empresas y centros de trabajo** (`Company:*List`): compartidos por todos los centros; basta con poder gestionar empresas.
- **Administración** (`Admin:OrganizationList`, `Admin:UserList`, importaciones): solo `ROLE_ADMIN`, comprobado en cada
  renderizado.
- **Acceso**: pantallas previas al acceso (`security/login_*`) con el diseño nuevo y datos personales / cambio de contraseña
  forzado con `ui/layout`. Todas extienden `security/login_base.html.twig` (panel de marca con el motivo de
  `_login_motif.html.twig` en pantallas grandes y cabecera de marca en las pequeñas; bloques `login_title`, `login_heading`,
  `login_subtitle` y `login_content`) y usan los controles de `login_macros.html.twig`. El campo de contraseña lleva el
  controlador Stimulus `password-visibility`, y `input[type=password]` usa la tipografía del sistema porque el Inter local
  no incluye el carácter con el que algunos móviles ocultan el texto.
- **Escritorio (`/`)**: saludo según la hora y el género del perfil y un panel por módulo (`Dashboard:ItpPanel`,
  `Dashboard:FctPanel`, `Dashboard:WltPanel`, en ese orden, sobre `AbstractTrackingPanel`). Cada panel solo se dibuja con
  acceso al seguimiento del módulo y reutiliza las mismas consultas acotadas que los listados (`WltModule\TrackingQueryFactory`
  para FP dual), de modo que cada rol cuenta solo lo que ya ve. «Datos personales» sale del menú y se abre desde el bloque de
  la persona de la barra lateral; los menús de sección (`ui/partial_menu.html.twig`) muestran la descripción de cada opción
  bajo el título en vez de un tooltip.
- **Migas**: se componen en `ui/layout.html.twig` (inicio con el nombre del centro, apartado y pantalla, con `>` como
  separador). Los elementos del `breadcrumb` de cada controlador que no son enlaces (persona, empresa, curso) pasan a
  subtítulo del título, salvo que el título ya los contenga.
- **Macros comunes de listados**: `lm.select_th` y `lm.select_cell` (casilla «seleccionar todo» y enlace a la ficha; admiten
  nombre de campo distinto de `items[]` y filas deshabilitadas).
- Cambios de nombre de parámetro: el borrado de profesorado lee `items[]` (antes `users[]`), como el resto.

## 5. Riesgos y mitigación

- **Tests funcionales**: ~6 ficheros dependen de selectores CSS; el resto usa rutas/texto, así que el riesgo es moderado. Cada módulo migrado debe pasar la suite completa (4090 tests) antes de continuar.
- **Convivencia Bootstrap/Tailwind**: el preflight de Tailwind rompe estilos de Bootstrap; por eso el layout nuevo va aparte y las páginas se migran completas, nunca a medias.
- **Calendarios y tablas de seguimiento**: son las pantallas más densas; se dejan para el final de cada módulo.
- **Formularios con entidades remotas** (`select2entity`): requieren endpoints compatibles con ux-autocomplete; se migran junto con el módulo que los usa.
- **Navegadores antiguos**: ya no se soportan desde la migración a AssetMapper.
