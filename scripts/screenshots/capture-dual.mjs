/**
 * Capturas de los capítulos de FP dual (docs/manual/img/dual-*.png), una cuenta por perfil.
 *
 * Necesita los datos de `make docs-data` y el servidor de `make docs-app`. Solo lee: no modifica datos.
 * Identificadores de los datos de demostración recién creados: proyecto 1 (coordina Elena Salas), programa
 * formativo 1 (Tecnologías del Sur), acuerdo 1 (Alejandro Pastor, tutoría docente Marta Jiménez, tutoría laboral
 * Sergio Navarro), docente 4 (Marta Jiménez), con su contacto 1, su reunión 1 y su gasto 1.
 */
import { launch, login, baseUrl, shot } from './lib.mjs';

const { newSession, close } = await launch();

async function run(profile, shots) {
    const page = await newSession();
    await login(page, profile);
    for (const [name, path, options] of shots) {
        const response = await page.goto(`${baseUrl}${path}`);
        await page.waitForLoadState('networkidle');
        const status = response?.status();
        console.log(`${status >= 400 ? '!! ' + status + ' ' : ''}${name}`);
        await shot(page, name, options);
    }
}

await run('administracion', [
    ['dual-inicio-gestion', '/dual'],
    ['dual-proyectos', '/dual/proyecto/listar/1/1'],
    ['dual-proyecto-formulario', '/dual/proyecto/1', { fullPage: true }],
    ['dual-proyecto-alumnado', '/dual/proyecto/estudiantes/1', { fullPage: true }],
    ['dual-programas', '/dual/programa/1/listar/1'],
    ['dual-programa-formulario', '/dual/programa/1', { fullPage: true }],
    ['dual-actividades', '/dual/proyecto/programa/1/actividad/1'],
    ['dual-actividad-formulario', '/dual/proyecto/programa/actividad/1', { fullPage: true }],
    ['dual-concreciones', '/dual/proyecto/programa/1/concrecion/1'],
    ['dual-concrecion-formulario', '/dual/proyecto/programa/1/detalles/concrecion', { fullPage: true }],
    ['dual-actividades-copiar', '/dual/proyecto/copiar/1', { fullPage: true }],
    ['dual-acuerdos', '/dual/acuerdo/1/listar/1'],
    ['dual-acuerdo-formulario', '/dual/acuerdo/1', { fullPage: true }],
    ['dual-acuerdo-calendario', '/dual/acuerdo/calendario/1', { fullPage: true }],
    ['dual-seguimiento-listado', '/dual/seguimiento/acuerdo/listar/1/1'],
    ['dual-evaluacion-listado', '/dual/convenio/evaluar/listar/1/1'],
    ['dual-evaluacion-formulario', '/dual/convenio/evaluar/1', { fullPage: true }],
    ['dual-resultados-listado', '/dual/resultado/estudiante/listar/1/1'],
    ['dual-reuniones', '/dual/reunion/listar/1/1'],
    ['dual-reunion-formulario', '/dual/reunion/1', { fullPage: true }],
    ['dual-contactos', '/dual/contacto/listar/1/1'],
    ['dual-contacto-informe-seguimiento', '/dual/contacto/informe/seguimiento/1/1'],
    ['dual-contacto-informe-centros', '/dual/contacto/informe/centros/1/1'],
    ['dual-gastos-resumen', '/dual/desplazamiento/resumen/1/1'],
    ['dual-informes-inicio', '/dual/informe/'],
    ['dual-informes-reuniones', '/dual/informe/reuniones/listar/1/1'],
    ['dual-informes-asistencia', '/dual/informe/asistencia/listar/1/1'],
    ['dual-informes-evaluacion', '/dual/informe/evaluacion/listar/1/1'],
    ['dual-informes-programa', '/dual/informe/programa_formativo/listar/1/1'],
    ['dual-informes-encuestas', '/dual/informe/encuesta/estudiantes/listar/1/1'],
]);

await run('coordinacion', [
    ['dual-inicio-coordinacion', '/dual'],
    ['dual-coordinacion-proyectos', '/dual/proyecto/listar/1/1'],
]);

await run('jefatura', [
    ['dual-inicio-jefatura', '/dual'],
]);

await run('tutoria', [
    ['dual-inicio-tutoria', '/dual'],
    ['dual-tutoria-seguimiento', '/dual/seguimiento/acuerdo/listar/1/1'],
    ['dual-tutoria-calendario', '/dual/seguimiento/calendario/1', { fullPage: true }],
    ['dual-tutoria-reuniones', '/dual/reunion/listar/1/1'],
    ['dual-tutoria-reunion-nueva', '/dual/reunion/nueva/1', { fullPage: true }],
    ['dual-tutoria-contactos', '/dual/contacto/listar/1/1'],
    ['dual-tutoria-contacto-nuevo', '/dual/contacto/nuevo', { fullPage: true }],
    ['dual-tutoria-gastos', '/dual/desplazamiento/4/listar/1'],
    ['dual-tutoria-gasto-nuevo', '/dual/desplazamiento/nuevo/4', { fullPage: true }],
    ['dual-tutoria-encuestas-alumnado', '/dual/encuesta/estudiante/1/1'],
    ['dual-tutoria-encuestas-empresa', '/dual/encuesta/empresa/1/1'],
    ['dual-tutoria-encuestas-centro', '/dual/encuesta/centro/1/1'],
    ['dual-tutoria-encuesta-centro-formulario', '/dual/encuesta/centro/cumplimentar/1/4', { fullPage: true }],
    ['dual-tutoria-evaluacion-listado', '/dual/convenio/evaluar/listar/1/1'],
    ['dual-jornada-registrada', '/dual/seguimiento/calendario/jornada/1', { fullPage: true }],
]);

await run('docenteGrupo', [
    ['dual-docente-inicio', '/dual'],
    ['dual-docente-evaluacion-listado', '/dual/convenio/evaluar/listar/1/1'],
    ['dual-docente-evaluacion-formulario', '/dual/convenio/evaluar/1', { fullPage: true }],
    ['dual-docente-resultados', '/dual/resultado/estudiante/listar/1/1'],
    // matrícula de Alejandro Pastor (5): el informe solo incluye las materias que da esta docente
    ['dual-docente-informe-resultados', '/dual/resultado/estudiante/5', { fullPage: true }],
]);

await run('alumnoDual', [
    ['dual-alumnado-inicio', '/dual'],
    ['dual-alumnado-calendario', '/dual/seguimiento/calendario/1', { fullPage: true }],
    ['dual-alumnado-jornada', '/dual/seguimiento/calendario/jornada/1', { fullPage: true }],
    ['dual-alumnado-encuesta', '/dual/encuesta/estudiante/cumplimentar/1', { fullPage: true }],
]);

await run('tutoriaLaboral', [
    ['dual-laboral-inicio', '/dual'],
    ['dual-laboral-seguimiento', '/dual/seguimiento/acuerdo/listar/1/1'],
    ['dual-laboral-calendario', '/dual/seguimiento/calendario/1', { fullPage: true }],
    ['dual-laboral-evaluacion-listado', '/dual/convenio/evaluar/listar/1/1'],
    ['dual-laboral-evaluacion-formulario', '/dual/convenio/evaluar/1', { fullPage: true }],
    ['dual-evaluacion-comentarios', '/dual/convenio/evaluar/comentarios/1', { fullPage: true }],
    ['dual-laboral-encuestas', '/dual/encuesta/empresa/1/1'],
    ['dual-laboral-encuesta-formulario', '/dual/encuesta/empresa/cumplimentar/1/30', { fullPage: true }],
]);

await run('economico', [
    ['dual-economico-inicio', '/dual'],
    ['dual-economico-resumen', '/dual/desplazamiento/resumen/1/1'],
    ['dual-economico-gastos', '/dual/desplazamiento/4/listar/1'],
    ['dual-economico-gasto-formulario', '/dual/desplazamiento/detalle/1', { fullPage: true }],
]);

await close();
