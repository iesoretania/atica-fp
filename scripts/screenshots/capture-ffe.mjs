/**
 * Capturas de los capítulos de FFE (docs/manual/img/ffe-*.png), una cuenta por perfil.
 *
 * Necesita los datos de `make docs-data` y el servidor de `make docs-app`. Solo lee: no modifica datos.
 * Identificadores de los datos de demostración recién creados: plan de formación 1 con un solo curso (2º DAM, curso 1) y
 * un solo grupo (2º DAM A, grupo del plan 1, tutoría dual de grupo Tomás Herrero); alumnado 1 (Adrián Ibáñez, estancia 1
 * en Tecnologías del Sur, responsable de seguimiento Julián Barrios, tutor dual Sergio Navarro) y 2 (Sara Gallego,
 * estancia 2 en Consultoría del Norte, responsable Marta Jiménez, tutora dual Patricia Durán). Jornadas: 74 (22/10,
 * confirmada), 76 (26/10, sin confirmar) y 73 (21/10, falta justificada) de la estancia de Sara. Docentes: 4 (Marta
 * Jiménez), 5 (Julián Barrios). Visita 1 y gasto 1, de Julián Barrios.
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
    return page;
}

const administracion = await run('administracion', [
    ['ffe-inicio-gestion', '/formacion'],
    ['ffe-planes', '/formacion/plan/listar/1/1'],
    ['ffe-plan-formulario', '/formacion/plan/1/1', { fullPage: true }],
    ['ffe-cursos', '/formacion/plan/curso/listar/1/1'],
    ['ffe-curso-horas', '/formacion/plan/curso/resultados/1', { fullPage: true }],
    ['ffe-grupos', '/formacion/plan/grupo/listar/1/1'],
    ['ffe-grupo-formulario', '/formacion/plan/grupo/1', { fullPage: true }],
    ['ffe-actividades', '/formacion/plan/actividad/listar/1/1'],
    ['ffe-actividad-formulario', '/formacion/plan/actividad/1', { fullPage: true }],
    ['ffe-actividades-importar', '/formacion/plan/actividad/importar/1', { fullPage: true }],
    ['ffe-empresas', '/formacion/plan/empresa/listar/1/1'],
    ['ffe-empresa-formulario', '/formacion/plan/empresa/1', { fullPage: true }],
    ['ffe-alumnado', '/formacion/plan/curso/grupo/estudiante/listar/1/1'],
    ['ffe-alumno-formulario', '/formacion/plan/curso/grupo/estudiante/1', { fullPage: true }],
    ['ffe-estancias', '/formacion/plan/curso/grupo/estudiante/estancia/listar/1/1'],
    ['ffe-estancia-formulario', '/formacion/plan/curso/grupo/estudiante/estancia/1', { fullPage: true }],
    ['ffe-estancias-gestion', '/formacion/plan/curso/grupo/gestion/listar/1/1'],
    ['ffe-estancias-lote', '/formacion/plan/curso/grupo/gestion/nueva/1', { fullPage: true }],
    ['ffe-estancia-calendario-gestion', '/formacion/plan/curso/grupo/gestion/estancia/calendario/1', { fullPage: true }],
    ['ffe-estancia-jornadas-nuevas', '/formacion/plan/curso/grupo/gestion/estancia/calendario/incorporar/1', { fullPage: true, fixDateTime: true }],
    ['ffe-seguimiento-listado', '/formacion/seguimiento/listar/1/1'],
    ['ffe-valoracion-listado', '/formacion/valoracion/listar/1/1'],
    ['ffe-resumen-grupos', '/formacion/valoracion/evaluacion/1/1'],
    ['ffe-resumen-alumnado', '/formacion/valoracion/evaluacion/grupo/1/1'],
    ['ffe-resumen-detalle', '/formacion/valoracion/evaluacion/grupo/detalle/1', { fullPage: true }],
    ['ffe-contactos', '/formacion/contacto/listar/1/1'],
    ['ffe-gastos-resumen', '/formacion/desplazamiento/resumen/1/1'],
    ['ffe-informes-inicio', '/formacion/informe/'],
    ['ffe-informes-alumnado', '/formacion/informe/encuesta/estudiantes/listar/1/1'],
    ['ffe-informes-empresas', '/formacion/informe/encuesta/empresas/listar/1/1'],
    ['ffe-informes-centro', '/formacion/informe/encuesta/centro/listar/1/1'],
]);

// copiar el calendario de una estancia a otras: se marca la estancia de destino y se pulsa el botón (solo muestra la confirmación)
await administracion.goto(`${baseUrl}/formacion/plan/curso/grupo/gestion/listar/1/1`);
await administracion.waitForLoadState('networkidle');
await administracion.locator('input[name="items[]"]').nth(1).check();
await administracion.locator('button[name="copy"]').click();
await administracion.waitForLoadState('networkidle');
console.log('ffe-estancias-copiar');
await shot(administracion, 'ffe-estancias-copiar', { fullPage: true });

await run('gestorFfe', [
    ['ffe-inicio-gestor', '/formacion'],
]);

await run('jefatura', [
    ['ffe-inicio-jefatura', '/formacion'],
]);

await run('tutoria', [
    ['ffe-inicio-tutoria', '/formacion'],
    ['ffe-tutoria-seguimiento', '/formacion/seguimiento/listar/1/1'],
    ['ffe-tutoria-calendario', '/formacion/seguimiento/calendario/2', { fullPage: true }],
    ['ffe-jornada-registrada', '/formacion/seguimiento/calendario/jornada/76', { fullPage: true }],
    ['ffe-jornada-bloqueada', '/formacion/seguimiento/calendario/jornada/74', { fullPage: true }],
    ['ffe-jornada-falta', '/formacion/seguimiento/calendario/jornada/73', { fullPage: true }],
    ['ffe-tutoria-valoracion-listado', '/formacion/valoracion/listar/1/1'],
    ['ffe-valoracion-formulario', '/formacion/valoracion/2', { fullPage: true }],
    ['ffe-valoracion-comentarios', '/formacion/valoracion/comentarios/3', { fullPage: true }],
    ['ffe-tutoria-resumen-alumnado', '/formacion/valoracion/evaluacion/grupo/1/1'],
    ['ffe-tutoria-contactos', '/formacion/contacto/listar/1/1'],
    ['ffe-tutoria-contacto-nuevo', '/formacion/contacto/nuevo', { fullPage: true, fixDateTime: true }],
    ['ffe-tutoria-gastos', '/formacion/desplazamiento/4/listar/1'],
    ['ffe-tutoria-gasto-nuevo', '/formacion/desplazamiento/nuevo/4', { fullPage: true, fixDateTime: true }],
    ['ffe-tutoria-encuestas-alumnado', '/formacion/encuesta/estudiante/1/1'],
    ['ffe-tutoria-encuestas-centro', '/formacion/encuesta/centro/1/1'],
    ['ffe-tutoria-encuesta-centro-formulario', '/formacion/encuesta/centro/cumplimentar/2/4', { fullPage: true }],
]);

await run('profesorado', [
    ['ffe-inicio-responsable', '/formacion'],
    ['ffe-responsable-seguimiento', '/formacion/seguimiento/listar/1/1'],
]);

await run('docenteGrupo', [
    ['ffe-docente-inicio', '/formacion'],
    ['ffe-docente-resumen-grupos', '/formacion/valoracion/evaluacion/1/1'],
    ['ffe-docente-resumen-alumnado', '/formacion/valoracion/evaluacion/grupo/1/1'],
    ['ffe-docente-resumen-detalle', '/formacion/valoracion/evaluacion/grupo/detalle/1', { fullPage: true }],
]);

await run('alumnoFfe', [
    ['ffe-alumnado-inicio', '/formacion'],
    ['ffe-alumnado-seguimiento', '/formacion/seguimiento/listar/1/1'],
    ['ffe-alumnado-calendario', '/formacion/seguimiento/calendario/1', { fullPage: true }],
    ['ffe-alumnado-jornada', '/formacion/seguimiento/calendario/jornada/26', { fullPage: true }],
    ['ffe-alumnado-encuesta', '/formacion/encuesta/estudiante/cumplimentar/1', { fullPage: true }],
]);

await run('tutoriaLaboral', [
    ['ffe-laboral-inicio', '/formacion'],
    ['ffe-laboral-seguimiento', '/formacion/seguimiento/listar/1/1'],
    ['ffe-laboral-calendario', '/formacion/seguimiento/calendario/1', { fullPage: true }],
    ['ffe-laboral-valoracion-listado', '/formacion/valoracion/listar/1/1'],
    ['ffe-laboral-valoracion-formulario', '/formacion/valoracion/1', { fullPage: true }],
    ['ffe-laboral-encuestas', '/formacion/encuesta/empresa/1/1'],
    ['ffe-laboral-encuesta-formulario', '/formacion/encuesta/empresa/cumplimentar/1/30', { fullPage: true }],
]);

await run('economico', [
    ['ffe-economico-inicio', '/formacion'],
    ['ffe-economico-resumen', '/formacion/desplazamiento/resumen/1/1'],
    ['ffe-economico-gastos', '/formacion/desplazamiento/5/listar/1'],
    ['ffe-economico-gasto-formulario', '/formacion/desplazamiento/detalle/1', { fullPage: true }],
]);

await close();
