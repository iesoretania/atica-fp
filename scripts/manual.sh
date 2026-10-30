#!/usr/bin/env bash
#
# Datos y servidor para las capturas del manual de usuario (ver docs/manual/CAPTURAS.md).
#
#   scripts/manual.sh data    recrea la base de datos del manual, carga las fixtures y los datos del manual
#   scripts/manual.sh serve   sirve la aplicación en http://127.0.0.1:8744 contra esa base de datos
#
# Se usa una base de datos APARTE de la de desarrollo, nunca la real: «fp_manual» (que el entorno de test
# convierte en «fp_manual_test»). El servidor se levanta en el entorno de desarrollo y apuntando
# explícitamente a «fp_manual_test».
#
# La conexión se toma de MANUAL_DATABASE_URL, o, si no está definida, se deriva de DATABASE_URL de
# .env.test.local cambiando el nombre de la base de datos por «fp_manual».
set -euo pipefail

cd "$(dirname "$0")/.."

PORT="${MANUAL_PORT:-8744}"

url() {
    if [[ -n "${MANUAL_DATABASE_URL:-}" ]]; then
        printf '%s' "$MANUAL_DATABASE_URL"
        return
    fi
    local base
    base=$(grep -h '^DATABASE_URL' .env.test.local 2>/dev/null | head -1 | sed -E 's/^DATABASE_URL=//; s/^"//; s/"$//') || true
    if [[ -z "$base" ]]; then
        echo "Define MANUAL_DATABASE_URL (mysql://usuario:clave@127.0.0.1:3306/fp_manual?serverVersion=...) o DATABASE_URL en .env.test.local." >&2
        exit 1
    fi
    printf '%s' "$base" | sed -E 's#/[^/?]+\?#/fp_manual?#'
}

case "${1:-}" in
    data)
        export DATABASE_URL
        DATABASE_URL="$(url)"
        # guarda: este script BORRA la base de datos; solo si es la del manual
        dbname=$(printf '%s' "$DATABASE_URL" | sed -E 's#^[^:]+://[^/]*/([^?]+).*#\1#')
        if [[ "$dbname" != *manual* ]]; then
            echo "La base de datos '${dbname}' no es la del manual (debe contener 'manual'): no se borra nada." >&2
            exit 1
        fi
        export APP_ENV=test
        php -d memory_limit=1G bin/console doctrine:database:drop --force --if-exists
        php -d memory_limit=1G bin/console doctrine:database:create
        php -d memory_limit=1G bin/console doctrine:migrations:migrate --no-interaction
        php -d memory_limit=1G bin/console doctrine:fixtures:load --append --no-interaction
        php -d memory_limit=1G bin/console app:manual:load-data
        ;;
    serve)
        export DATABASE_URL
        # el entorno de test añade «_test» al nombre; en desarrollo hay que indicarlo a mano
        DATABASE_URL="$(url | sed -E 's#/fp_manual\?#/fp_manual_test?#')"
        export APP_ENV=dev
        # el .env.local de desarrollo rotula la aplicación como «ÁTICA FP DEV»: las capturas deben llevar el nombre real
        export APP_NAME="ÁTICA FP"
        echo "Sirviendo el manual en http://127.0.0.1:${PORT} (base de datos fp_manual_test). Ctrl+C para detenerlo."
        # -d variables_order=EGPCS es imprescindible: sin él PHP ignora el DATABASE_URL exportado y usaría
        # el de .env.local, es decir, la base de datos de desarrollo REAL.
        exec php -d variables_order=EGPCS -S "127.0.0.1:${PORT}" -t public scripts/router-shots.php
        ;;
    *)
        echo "Uso: $0 data|serve" >&2
        exit 1
        ;;
esac
