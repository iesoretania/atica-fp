.PHONY: docs docs-pdf docs-web docs-serve

# Versión publicada, leída de config/services.yaml (app.version). La portada del
# manual en PDF y el pie de la web la muestran automáticamente, así que en cada
# release basta con actualizar services.yaml: no hay que tocar el manual a mano.
VERSION := $(shell grep -E '^[[:space:]]*app\.version:' config/services.yaml | head -1 | sed -E 's/.*"([^"]+)".*/\1/')
# Fecha del release (app.pub_date), reformateada YYYY-MM-DD -> DD/MM/YYYY para
# mostrarla junto a la versión en la portada del manual.
PUB_DATE := $(shell grep -E '^[[:space:]]*app\.pub_date:' config/services.yaml | head -1 | sed -E 's/.*"([0-9]+)-([0-9]+)-([0-9]+)".*/\3\/\2\/\1/')

# Capítulos del manual, en orden: index.md y los ficheros NN-*.md
MANUAL_CHAPTERS := docs/manual/index.md $(sort $(wildcard docs/manual/[0-9][0-9]-*.md))

## Genera el manual completo: PDF y web.
docs: docs-pdf docs-web

## Genera el manual en PDF (docs/manual/atica-fp-manual.pdf).
##
## Usa pandoc (Markdown -> HTML) y "npx pagedjs-cli" (Chromium headless) para
## imprimir el HTML con el tema CSS. El HTML intermedio (_build.html) se genera
## en docs/manual/ para que las rutas relativas a assets/ e img/ resuelvan igual
## que en la web.
##
## pagedjs-cli usa Puppeteer; reutilizamos el Chrome del sistema vía
## PUPPETEER_EXECUTABLE_PATH para no descargar un Chromium aparte. Si no se
## encuentra, instala uno con "npx puppeteer browsers install chrome".
docs-pdf:
	@command -v pandoc >/dev/null 2>&1 || { echo "Necesitas pandoc. Instálalo (p. ej. brew install pandoc) y reintenta."; exit 1; }
	@command -v npx >/dev/null 2>&1 || { echo "Necesitas Node.js/npx para generar el PDF. Instala Node y reintenta."; exit 1; }
	pandoc -s -f markdown-auto_identifiers --toc --toc-depth=2 \
		--lua-filter=docs/pandoc-admonitions.lua \
		--lua-filter=docs/pandoc-internal-links.lua \
		--metadata title="Manual de usuario de ÁTICA FP" \
		--metadata subtitle="Gestión de la formación en centros de trabajo: FCT, FP dual y FFE" \
		--metadata date="Versión $(VERSION) · $(PUB_DATE)" \
		--metadata lang=es \
		-c assets/theme.css -c assets/print.css \
		-o docs/manual/_build.html \
		$(MANUAL_CHAPTERS)
	cd docs/manual && CHROME="$${PUPPETEER_EXECUTABLE_PATH:-$$(for c in \
		"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" \
		"/Applications/Chromium.app/Contents/MacOS/Chromium" \
		"$$(command -v google-chrome)" "$$(command -v chromium)" "$$(command -v chromium-browser)"; do \
		[ -x "$$c" ] && echo "$$c" && break; done)}"; \
		PUPPETEER_EXECUTABLE_PATH="$$CHROME" npx --yes pagedjs-cli _build.html -o atica-fp-manual.pdf

## Construye la web del manual (docs/manual-site/) con MkDocs Material.
##
## Requiere MkDocs Material: pip install -r docs/manual/requirements.txt
docs-web:
	@command -v mkdocs >/dev/null 2>&1 || { echo "Necesitas MkDocs Material: pip install -r docs/manual/requirements.txt"; exit 1; }
	MANUAL_COPYRIGHT="ÁTICA FP · versión $(VERSION) · $(PUB_DATE)" mkdocs build -f docs/manual/mkdocs.yml

## Previsualiza la web del manual en local (http://127.0.0.1:8000).
docs-serve:
	@command -v mkdocs >/dev/null 2>&1 || { echo "Necesitas MkDocs Material: pip install -r docs/manual/requirements.txt"; exit 1; }
	MANUAL_COPYRIGHT="ÁTICA FP · versión $(VERSION) · $(PUB_DATE)" mkdocs serve -f docs/manual/mkdocs.yml
