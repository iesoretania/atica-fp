# Fichas de referencia rápida

Fuente de las fichas de referencia rápida («cheatsheets») de ÁTICA FP: una página A4 por cada tarea
frecuente de un perfil (por ejemplo, registrar una visita o la jornada del día), pensadas para
consultarse en el móvil o imprimirse. Todos los comandos de esta página se ejecutan desde la raíz del
repositorio con `make`, no directamente desde esta carpeta.

Todavía no hay fichas: se redactan después de los capítulos del manual de los que salen (ver
[`../manual/PLAN.md`](../manual/PLAN.md), apartado «Fichas de referencia rápida»).

## Ficheros

- `<nombre>.md` — una ficha [Marp](https://marp.app) por tarea. Cada una lleva en su cabecera
  `marp: true`, `theme: atica-fp-cheatsheet`, `html: true` y el pie
  `footer: 'v{{VERSION}} ({{PUB_DATE}}) · ÁTICA FP'`.
- `theme.css` — tema Marp compartido (página A4 vertical, paleta azul de la aplicación).
- `img/` — capturas de pantalla referenciadas desde las fichas (capturas de móvil).
- `ficha-*.pdf` y `_build.md` — salidas generadas por `make cheatsheets`; no se editan a mano ni se
  versionan.

## Generar los PDF

```bash
make cheatsheets
```

Genera un PDF independiente por ficha (`docs/cheatsheets/ficha-<nombre>.pdf`) con
[marp-cli](https://github.com/marp-team/marp-cli) (vía `npx`), usando el tema compartido. Requiere
Node.js/`npx`. La versión y la fecha del pie se toman automáticamente de `app.version`/`app.pub_date`
en `config/services.yaml`, igual que en el manual. `make docs` genera también las fichas.
