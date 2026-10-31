/**
 * Utilidades comunes de los scripts de capturas del manual (docs/manual/CAPTURAS.md).
 *
 * Playwright NO es dependencia del proyecto: se instala fuera del repositorio y se indica dónde con
 * PLAYWRIGHT_DIR (el directorio que contiene node_modules/playwright). Sin esa variable se intenta
 * resolver «playwright» de la forma habitual.
 *
 * Variables de entorno:
 *   SHOTS_BASE_URL  servidor de la aplicación (por defecto http://127.0.0.1:8744, el de «make docs-app»)
 *   SHOTS_OUT_DIR   directorio de salida (por defecto docs/manual/img)
 *   PLAYWRIGHT_DIR  directorio con node_modules/playwright
 *   SHOTS_NOW       fecha y hora que ve el navegador (por defecto 2026-10-31T10:30:00+01:00)
 *
 * Chromium no pinta el texto exactamente igual en dos ejecuciones (decenas de píxeles de suavizado), así que
 * una captura solo reemplaza a la imagen existente si difiere de ella más de MAX_DIFF_PIXELS píxeles
 * (o cambia de tamaño). Así, volver a ejecutar un script no modifica en git imágenes que no han cambiado.
 * La comparación usa `pixelmatch` y `pngjs`, que se instalan junto a Playwright (ver CAPTURAS.md); sin ellos
 * se escriben siempre. SHOTS_FORCE=1 escribe siempre.
 */
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';

export const baseUrl = process.env.SHOTS_BASE_URL ?? 'http://127.0.0.1:8744';
export const outDir = process.env.SHOTS_OUT_DIR ?? 'docs/manual/img';

/**
 * «Ahora» de las capturas: el mismo día en que `scripts/manual.sh data` sitúa los datos de demostración
 * (DEMO_TODAY, 2026-10-31). Se puede cambiar con SHOTS_NOW (formato ISO), pero entonces el navegador y los datos
 * discrepan y cambian las imágenes que dependen de la fecha.
 */
export const fixedNow = new Date(process.env.SHOTS_NOW ?? '2026-10-31T10:30:00+01:00');

/** Contraseña de todos los usuarios de demostración (DemoData::PASSWORD) */
const PASSWORD = 'demo1234';

/**
 * Usuario de demostración de cada perfil (ver LoadManualDataCommand::PROFILES, que es la fuente).
 * El administrador de la plataforma conserva el usuario «admin» de las fixtures.
 */
export const accounts = {
    plataforma: 'admin',
    administracion: 'avalverde',
    direccion: 'lortega',
    economico: 'ireyes',
    jefatura: 'rcabrera',
    tutoria: 'mjimenez', // tutoría docente y de grupo
    profesorado: 'jbarrios',
    docenteGrupo: 'jcortes', // da clase en 2º DAM A y no tiene otro cargo (evalúa en FP dual y FFE)
    coordinacion: 'esalas', // coordinación de FP dual
    gestorFfe: 'therrero',
    tutoriaLaboral: 'snavarro',
    tutoriaLaboral2: 'pduran',
    alumnoFct: 'dfernandez', // acuerdo de FCT
    alumnoDual: 'apastor', // acuerdo de FP dual
    alumnoFfe: 'aibanez', // estancia de FFE
};

async function loadPlaywright() {
    if (process.env.PLAYWRIGHT_DIR) {
        return import(pathToFileURL(resolve(process.env.PLAYWRIGHT_DIR, 'node_modules/playwright/index.mjs')).href);
    }
    return import('playwright');
}

/**
 * Abre un navegador y devuelve una función para crear páginas con la configuración de las capturas.
 * Cada llamada a newSession() es una sesión nueva (sin cookies), así que sirve para cambiar de perfil.
 */
export async function launch({ width = 1360, height = 800 } = {}) {
    mkdirSync(outDir, { recursive: true });
    const { chromium } = await loadPlaywright();
    const browser = await chromium.launch({ args: ['--lang=es-ES'] });

    return {
        browser,
        async newSession(options = {}) {
            const context = await browser.newContext({ viewport: { width, height }, locale: 'es-ES', ...options });
            // Reloj del navegador fijo: lo que dependa de «hoy» (calendarios, selectores de fecha, valores por
            // defecto) sale igual en cada ejecución y no cambia imágenes que no tienen que ver con el cambio.
            await context.clock.setFixedTime(fixedNow);
            // La aplicación carga la tipografía (Google Fonts) de internet. Con conexión se ve como en producción;
            // sin ella la petición no termina nunca y bloquea la carga de la página, así que se aborta a los 4 s.
            await context.route(/^https?:\/\/(?!127\.0\.0\.1|localhost)/, async route => {
                try {
                    await route.fulfill({ response: await route.fetch({ timeout: 4000 }) });
                } catch {
                    await route.abort();
                }
            });
            return context.newPage();
        },
        close: () => browser.close(),
    };
}

/** Inicia sesión con el usuario indicado (una clave de `accounts` o un nombre de usuario) */
export async function login(page, profileOrUsername) {
    const username = accounts[profileOrUsername] ?? profileOrUsername;
    await page.goto(`${baseUrl}/entrar`);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="_username"]', username);
    await page.fill('input[name="_password"]', PASSWORD);
    await page.click('form[action="/comprobar"] button[type="submit"]');
    await page.waitForLoadState('networkidle');
}

/**
 * Oculta la barra de depuración de Symfony (solo existe en el entorno de desarrollo) y sube al principio:
 * una captura de página completa de una página con scroll descoloca los elementos fijos.
 */
export async function prepare(page) {
    await page.addStyleTag({ content: 'div[id^="sfwdt"], .sf-toolbar { display: none !important; }' });
    await page.evaluate(() => window.scrollTo(0, 0));
    // sin esperar a la tipografía, algunas capturas salen con el texto a medio sustituir
    await page.evaluate(() => document.fonts.ready);
    // el puntero se queda donde se pulsó el botón de entrada y resaltaría el elemento que haya debajo
    await page.mouse.move(0, 0);
}

/** Va a una ruta de la aplicación y espera a que termine de cargar */
export async function open(page, path) {
    await page.goto(`${baseUrl}${path}`);
    await page.waitForLoadState('networkidle');
}

/**
 * Guarda una captura en el directorio de salida. `name` es el nombre sin extensión y lleva el prefijo del
 * capítulo (por ejemplo, «primeros-pasos-acceso»). Con `selector` se captura solo ese elemento.
 */
export async function shot(page, name, { selector, fullPage = false, clip } = {}) {
    await prepare(page);
    const path = `${outDir}/${name}.png`;
    const options = { animations: 'disabled', caret: 'hide' };
    const image = selector
        ? await page.locator(selector).first().screenshot(options)
        : await page.screenshot({ ...options, fullPage, clip });
    if (isSameImage(path, image)) {
        console.log(`  ${path} (sin cambios)`);
        return;
    }
    writeFileSync(path, image);
    console.log(`  ${path}`);
}

/**
 * Píxeles distintos (según pixelmatch, que ignora diferencias imperceptibles) que se toleran como ruido. Medido:
 * dos ejecuciones seguidas difieren en unas decenas de píxeles; un cambio real de texto, en cientos.
 */
const MAX_DIFF_PIXELS = 100;

/** ¿La captura nueva es la misma que la imagen guardada, salvo el ruido de suavizado del texto? */
function isSameImage(path, image) {
    if (process.env.SHOTS_FORCE || !existsSync(path)) {
        return false;
    }
    try {
        const require = createRequire(resolve(process.env.PLAYWRIGHT_DIR ?? '.', 'package.json'));
        const { PNG } = require('pngjs');
        const pixelmatch = require('pixelmatch');
        const a = PNG.sync.read(readFileSync(path));
        const b = PNG.sync.read(image);
        if (a.width !== b.width || a.height !== b.height) {
            return false;
        }
        return pixelmatch(a.data, b.data, null, a.width, a.height, { threshold: 0.1 }) <= MAX_DIFF_PIXELS;
    } catch {
        return false;
    }
}
