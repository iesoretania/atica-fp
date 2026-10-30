/**
 * Capturas de los capítulos de FCT (docs/manual/img/fct-*.png), una cuenta por perfil.
 *
 * Necesita los datos de `make docs-data` y el servidor de `make docs-app`. Solo lee: no modifica datos.
 * Los identificadores son los de los datos de demostración recién creados: convocatoria 1, acuerdo 1 (con
 * Daniel Fernández, matrícula de acuerdo 1, y Carla Romero, la 2), tutoría docente Marta Jiménez (docente 4),
 * su visita 1 y su gasto 1.
 */
import { launch, login, open, shot } from './lib.mjs';

const { newSession, close } = await launch();

async function run(profile, shots) {
    const page = await newSession();
    await login(page, profile);
    for (const [name, path, options] of shots) {
        console.log(name);
        await open(page, path);
        await shot(page, name, options);
    }
    return page;
}

// ── Administración del centro: gestión del módulo ───────────────────────────
await run('administracion', [
    ['fct-inicio-gestion', '/fct'],
    ['fct-convocatorias', '/fct/convocatoria/listar/1/1'],
    ['fct-convocatoria-formulario', '/fct/convocatoria/1', { fullPage: true }],
    ['fct-actividades', '/fct/actividad/1/listar/1'],
    ['fct-actividad-formulario', '/fct/actividad/nueva/1', { fullPage: true }],
    ['fct-actividad-copiar', '/fct/actividad/copiar/1', { fullPage: true }],
    ['fct-acuerdos', '/fct/acuerdo/1/listar/1'],
    ['fct-acuerdo-formulario', '/fct/acuerdo/1', { fullPage: true }],
    ['fct-acuerdo-alumno', '/fct/acuerdo/estudiante/1', { fullPage: true }],
    ['fct-acuerdo-calendario', '/fct/convenio/calendario/1', { fullPage: true }],
    ['fct-acuerdo-calendario-anadir', '/fct/convenio/calendario/1/incorporar', { fullPage: true }],
    ['fct-seguimiento-listado', '/fct/seguimiento/acuerdo/listar/1/1'],
    ['fct-informes-inicio', '/fct/informe/'],
    ['fct-informes-encuestas', '/fct/informe/encuesta/estudiantes/listar/1/1'],
    ['fct-visitas-resumen', '/fct/visita/resumen/1/1'],
    ['fct-gastos-resumen', '/fct/desplazamiento/resumen/1/1'],
]);

// ── Jefatura de departamento ────────────────────────────────────────────────
await run('jefatura', [
    ['fct-inicio-jefatura', '/fct'],
]);

// ── Tutoría docente y de grupo ──────────────────────────────────────────────
await run('tutoria', [
    ['fct-inicio-tutoria', '/fct'],
    ['fct-tutoria-seguimiento', '/fct/seguimiento/acuerdo/listar/1/1'],
    ['fct-tutoria-calendario', '/fct/seguimiento/calendario/1', { fullPage: true }],
    ['fct-tutoria-visitas', '/fct/visita/4/listar/1'],
    ['fct-tutoria-visita-formulario', '/fct/visita/nueva/4', { fullPage: true }],
    ['fct-tutoria-gastos', '/fct/desplazamiento/4/listar/1'],
    ['fct-tutoria-gasto-formulario', '/fct/desplazamiento/nuevo/4', { fullPage: true }],
    ['fct-tutoria-informe-listado', '/fct/informe/acuerdo/1/1'],
    ['fct-tutoria-informe-formulario', '/fct/informe/cumplimentar/1', { fullPage: true }],
    ['fct-tutoria-encuestas-alumnado', '/fct/encuesta/estudiante/1/1'],
    ['fct-tutoria-encuestas-empresa', '/fct/encuesta/empresa/1/1'],
    ['fct-tutoria-encuestas-centro', '/fct/encuesta/centro/1/1'],
    ['fct-tutoria-encuesta-centro-formulario', '/fct/encuesta/centro/cumplimentar/1/4', { fullPage: true }],
    // jornada con actividades registradas (18 de septiembre) y jornada pendiente (3 de noviembre)
    ['fct-jornada-registrada', '/fct/seguimiento/calendario/jornada/1/1', { fullPage: true }],
    ['fct-jornada-pendiente', '/fct/seguimiento/calendario/jornada/33/1', { fullPage: true }],
]);

// ── Alumnado ────────────────────────────────────────────────────────────────
await run('alumnoFct', [
    ['fct-alumnado-inicio', '/fct'],
    ['fct-alumnado-calendario', '/fct/seguimiento/calendario/1', { fullPage: true }],
    ['fct-alumnado-jornada', '/fct/seguimiento/calendario/jornada/33/1', { fullPage: true }],
    ['fct-alumnado-encuesta', '/fct/encuesta/estudiante/cumplimentar/1', { fullPage: true }],
]);

// ── Tutoría laboral ─────────────────────────────────────────────────────────
await run('tutoriaLaboral', [
    ['fct-laboral-inicio', '/fct'],
    ['fct-laboral-seguimiento', '/fct/seguimiento/acuerdo/listar/1/1'],
    ['fct-laboral-calendario', '/fct/seguimiento/calendario/1', { fullPage: true }],
    ['fct-laboral-informe-listado', '/fct/informe/acuerdo/1/1'],
    ['fct-laboral-informe', '/fct/informe/cumplimentar/1', { fullPage: true }],
    ['fct-laboral-encuestas', '/fct/encuesta/empresa/1/1'],
    ['fct-laboral-encuesta-formulario', '/fct/encuesta/empresa/cumplimentar/1/30', { fullPage: true }],
    ['fct-laboral-jornada', '/fct/seguimiento/calendario/jornada/1/1', { fullPage: true }],
]);

// ── Responsable económico ───────────────────────────────────────────────────
await run('economico', [
    ['fct-economico-inicio', '/fct'],
    ['fct-economico-resumen', '/fct/desplazamiento/resumen/1/1'],
    ['fct-economico-gastos', '/fct/desplazamiento/4/listar/1'],
    ['fct-economico-gasto-formulario', '/fct/desplazamiento/detalle/1', { fullPage: true }],
]);

await close();
