# Manual de usuario

Fuente del manual de usuario de ÁTICA FP, escrito en Markdown y publicado en dos formatos: un PDF
descargable y una web navegable. Todos los comandos de esta página se ejecutan desde la raíz del
repositorio con `make`, no directamente desde esta carpeta.

El mecanismo es el mismo que usa [ÁTICA Calidad](https://github.com/reasol-edu/atica-calidad). El plan
de redacción (estructura de capítulos, perfiles, matrices de permisos y capturas) está en
[`PLAN.md`](PLAN.md).

## Ficheros

- `index.md` y `NN-*.md` — los capítulos del manual, en el orden de su prefijo numérico, tanto en el
  PDF como en el índice de la web.
- `mkdocs.yml` — configuración de MkDocs Material para la versión web (navegación, tema, exclusiones).
- `requirements.txt` — dependencias de Python para generar la web (MkDocs Material).
- `assets/` — hojas de estilo (`theme.css`, `print.css`), logotipo y favicon, compartidos por el PDF y
  la web.
- `img/` — capturas de pantalla y otras imágenes referenciadas desde los capítulos.
- `atica-fp-manual.pdf` y `_build.html` — salidas generadas por `make docs-pdf`; no se editan a mano ni
  se versionan.
- `../manual-site/` — salida generada por `make docs-web`; tampoco se versiona.
- `../pandoc-admonitions.lua` y `../pandoc-internal-links.lua` — filtros de pandoc para el PDF:
  traducen los avisos (`!!! note`) y los enlaces entre capítulos.

## Generar el PDF

```bash
make docs-pdf
```

Genera `docs/manual/atica-fp-manual.pdf` a partir de todos los capítulos, usando
[pandoc](https://pandoc.org) para convertir el Markdown a HTML (fichero intermedio `_build.html`,
en esta misma carpeta para que las rutas relativas a `assets/` e `img/` resuelvan igual que en la web)
y [`pagedjs-cli`](https://pagedjs.org) (vía `npx`, sobre un Chrome/Chromium local) para maquetar e
imprimir ese HTML a PDF con el mismo tema visual que la web.

Requiere tener instalados `pandoc` y Node.js/`npx`; el mensaje de error del propio comando indica
cómo instalar lo que falte. La versión y la fecha de la portada se toman automáticamente de
`app.version`/`app.pub_date` en `config/services.yaml`: no hace falta editar el manual en cada
versión.

## Generar / previsualizar la web

```bash
make docs-web      # genera docs/manual-site/ (build estático)
make docs-serve    # sirve el manual en http://127.0.0.1:8000 con recarga en caliente
```

Ambos usan [MkDocs Material](https://squidfunk.github.io/mkdocs-material/). Requieren las
dependencias de `requirements.txt`:

```bash
pip install -r docs/manual/requirements.txt
```

`docs-serve` es la forma más rápida de revisar cambios mientras se edita: recarga el navegador
automáticamente al guardar cualquier fichero Markdown.

## Generar ambas salidas

```bash
make docs
```

Equivale a ejecutar `make docs-pdf` y `make docs-web`.

## Añadir un capítulo

1. Crea el fichero `NN-nombre.md` con el número que le corresponda en el plan. El PDF incluye
   automáticamente todos los ficheros `NN-*.md`, ordenados por nombre.
2. Añádelo a `nav` en `mkdocs.yml`, dentro de su sección: la web **no** descubre los capítulos sola.
3. Enlaza entre capítulos con rutas relativas a ficheros (`[texto](04-administrar-el-centro.md#ancla)`):
   funcionan en la web y el filtro de pandoc los convierte en enlaces internos en el PDF.
4. Las imágenes van en `img/` con un nombre que empiece por el del capítulo, y siempre con texto
   alternativo que describa lo que se ve.

## Regenerar las capturas

Las capturas salen de scripts de Node/[Playwright](https://playwright.dev) en `scripts/screenshots/`,
ejecutados contra una base de datos **desechable** con datos de demostración (`make docs-data`) y la
aplicación servida con `make docs-app`, nunca contra datos reales. El procedimiento, los usuarios de
demostración y el catálogo de capturas están en [`CAPTURAS.md`](CAPTURAS.md).
