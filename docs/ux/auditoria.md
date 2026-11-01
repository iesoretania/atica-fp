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
  página (escribir en el filtro reemplaza la entrada) y `popstate` llama a la acción `syncFromUrl`. Resultado: volver desde una
  ficha o con «Atrás» deja el listado como estaba (`?q=...&page=...`).
- **Formularios** (`ComponentWithFormTrait`): el componente solo repinta los campos dependientes mientras se edita; el
  guardado sigue siendo un envío normal al controlador. Una entidad nueva no se puede hidratar por id, así que el componente
  guarda el docente y los valores del formulario y construye la entidad en cada petición. La propiedad no puede llamarse como el
  formulario (`visit`). Los campos que pasan de `<select>` a casillas necesitan normalizar su valor a lista.
- **Seguridad**: cada actualización llega a `/_components/...` como una petición nueva, así que los permisos se comprueban en
  cada una (`authorize()`/`#[PostHydrate]`), no solo en el controlador de la página.

## 5. Riesgos y mitigación

- **Tests funcionales**: ~6 ficheros dependen de selectores CSS; el resto usa rutas/texto, así que el riesgo es moderado. Cada módulo migrado debe pasar la suite completa (4090 tests) antes de continuar.
- **Convivencia Bootstrap/Tailwind**: el preflight de Tailwind rompe estilos de Bootstrap; por eso el layout nuevo va aparte y las páginas se migran completas, nunca a medias.
- **Calendarios y tablas de seguimiento**: son las pantallas más densas; se dejan para el final de cada módulo.
- **Formularios con entidades remotas** (`select2entity`): requieren endpoints compatibles con ux-autocomplete; se migran junto con el módulo que los usa.
- **Navegadores antiguos**: ya no se soportan desde la migración a AssetMapper.
