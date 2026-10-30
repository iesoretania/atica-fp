/**
 * Capturas de la parte general del manual (capítulos 02 a 08): acceso, curso académico, administración del
 * centro, importaciones, empresas, encuestas y plataforma.
 *
 * Necesita los datos de `make docs-data` y el servidor de `make docs-app`. Solo lee: no modifica datos.
 * Las rutas usan el curso 2026-2027 del centro «I.E.S. Ada Lovelace» (id 1 en los datos de demostración).
 */
import { launch, login, open, shot } from './lib.mjs';

const { newSession, close } = await launch();

/** [nombre de la imagen, ruta, opciones de captura] */
const administracion = [
    // 03 · preparar el curso
    ['preparar-curso-listado', '/centro/cursoacademico/listar/1'],
    ['preparar-curso-formulario', '/centro/cursoacademico/1'],
    ['preparar-curso-copiar', '/centro/cursoacademico/copiar/1'],
    ['preparar-curso-inicio-centro', '/centro'],
    // 04 · administrar el centro
    ['centro-profesorado', '/centro/profesorado/listar/1/1'],
    ['centro-profesorado-formulario', '/centro/profesorado/nuevo'],
    ['centro-matriculas', '/centro/matricula/listar/1/1'],
    ['centro-departamentos', '/centro/departamento/listar/1/1'],
    ['centro-ensenanzas', '/centro/ensenanza/listar/1/1'],
    ['centro-ensenanza-formulario', '/centro/ensenanza/1'],
    ['centro-niveles', '/centro/nivel/listar/1/1'],
    ['centro-grupos', '/centro/grupo/listar/1/1'],
    ['centro-grupo-formulario', '/centro/grupo/1'],
    ['centro-materias', '/centro/materia/listar/1/1'],
    ['centro-resultados', '/centro/ensenanza/materia/1/resultado/1/'],
    ['centro-dias-no-lectivos', '/centro/dia_no_lectivo/listar/1/1'],
    ['centro-metodos-contacto', '/centro/contactos/listar/1/1'],
    ['centro-roles', '/centro/rol'],
    ['centro-itinerarios', '/centro/itinerario/listar/1'],
    ['centro-escalas', '/centro/escala/listar/1/'],
    // 05 · importaciones
    ['importaciones-inicio', '/centro/importar'],
    ['importaciones-profesorado', '/centro/importar/profesorado'],
    ['importaciones-grupos', '/centro/importar/grupo'],
    ['importaciones-alumnado', '/centro/importar/estudiante'],
    ['importaciones-claves', '/centro/importar/estudiante/login'],
    ['importaciones-dias-no-lectivos', '/centro/importar/dia_no_lectivo'],
    // 06 · empresas
    ['empresas-listado', '/empresa/listar/1'],
    ['empresas-formulario', '/empresa/1'],
    ['empresas-sedes', '/empresa/1/sede/listar/1'],
    // 07 · encuestas y plantillas
    ['encuestas-listado', '/centro/encuesta/listar/1'],
    ['plantillas-listado', '/centro/plantilla/listar/1'],
];

const sesionAdministracion = await newSession();
await login(sesionAdministracion, 'administracion');
for (const [name, path, options] of administracion) {
    console.log(name);
    await open(sesionAdministracion, path);
    await shot(sesionAdministracion, name, options);
}

// ── 02 · acceso sin sesión y recuperación de contraseña ─────────────────────
const anonimo = await newSession();
await open(anonimo, '/restablecer');
await shot(anonimo, 'primeros-pasos-recuperar-contrasena');

// ── 08 · plataforma ─────────────────────────────────────────────────────────
const plataforma = await newSession();
await login(plataforma, 'plataforma');
for (const [name, path] of [
    ['plataforma-organizaciones', '/admin/organizaciones/listar/1'],
    ['plataforma-organizacion-formulario', '/admin/organizaciones/1'],
    ['plataforma-usuarios', '/admin/usuarios/listar/1'],
    ['plataforma-usuario-formulario', '/admin/usuarios/1'],
]) {
    console.log(name);
    await open(plataforma, path);
    await shot(plataforma, name);
}

await close();
