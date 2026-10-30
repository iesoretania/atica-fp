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
 */
import { mkdirSync } from 'node:fs';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';

export const baseUrl = process.env.SHOTS_BASE_URL ?? 'http://127.0.0.1:8744';
export const outDir = process.env.SHOTS_OUT_DIR ?? 'docs/manual/img';

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
    if (selector) {
        await page.locator(selector).first().screenshot({ path });
    } else {
        await page.screenshot({ path, fullPage, clip });
    }
    console.log(`  ${path}`);
}
