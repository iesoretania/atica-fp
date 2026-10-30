/**
 * Capturas del capítulo «Primeros pasos» (docs/manual/img/primeros-pasos-*.png): pantalla de acceso, la página
 * de inicio con el menú de un perfil y los datos personales.
 *
 * Necesita los datos de `make docs-data` y el servidor de `make docs-app`. Solo lee: no modifica datos.
 */
import { launch, login, open, shot } from './lib.mjs';

const { newSession, close } = await launch();

// ── 1. Pantalla de acceso, sin sesión ───────────────────────────────────────
const anonimo = await newSession();
await open(anonimo, '/entrar');
await shot(anonimo, 'primeros-pasos-acceso');

// ── 2. Inicio con el menú de la administración del centro ───────────────────
const administracion = await newSession();
await login(administracion, 'administracion');
await shot(administracion, 'primeros-pasos-inicio');

// ── 3. Datos personales ─────────────────────────────────────────────────────
await open(administracion, '/datos');
await shot(administracion, 'primeros-pasos-datos-personales');

await close();
