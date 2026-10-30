<?php

namespace App\Tests\Functional\Permissions;

/**
 * Definición de las matrices de permisos del manual de usuario: qué acciones se comprueban en cada módulo y con
 * qué perfiles. El resultado de pedir cada pantalla con cada perfil se compara con la instantánea versionada
 * (tests/Functional/Permissions/snapshots/*.json) en PermissionMatrixTest; de esa misma instantánea sale la tabla
 * del manual (scripts/render-permission-matrix.php). Así la matriz publicada no puede desfasarse del código.
 *
 * Cada acción es una petición de solo lectura (GET) o un POST que nunca modifica datos: los de borrado envían
 * el elemento pero no la confirmación, así que la aplicación comprueba el permiso (también el del propio
 * elemento) y, si lo hay, muestra la página de confirmación en lugar de borrar. Formato de la petición:
 * «MÉTODO /ruta» y, para un POST, un tercer fragmento con los datos («items[]={visit}»).
 * «Permitido» es una respuesta 2xx o 3xx; «denegado», un 403.
 *
 * Marcadores que se sustituyen por datos de las fixtures (ver PermissionMatrixTest::placeholders()):
 *   {year}        curso académico actual del centro
 *   {shift}       convocatoria de FCT
 *   {agreement}   acuerdo de FCT del alumno 1
 *   {enrollment}  alumno 1 en ese acuerdo (matrícula de acuerdo)
 *   {activity}    primera actividad de la convocatoria
 *   {teacher}     docente «tutor», responsable de seguimiento del alumno 1
 *   {visit}       visita de ese docente
 *   {expense}     gasto de desplazamiento de ese docente
 *   {workTutor}   tutor laboral del acuerdo
 *
 * Perfiles: se identifican con una clave y apuntan a un usuario de las fixtures. Los que dependen de su
 * relación con un acuerdo concreto (tutoría docente, alumnado, tutoría laboral) lo hacen respecto al
 * acuerdo del alumno 1; el resto del profesorado se muestra como «otro» para enseñar la diferencia.
 */
final class PermissionMatrix
{
    /**
     * @return array<string, array{title: string, profiles: array<string, array{label: string, user: string}>, sections: list<array{title: string, actions: list<array{label: string, request: string}>}>}>
     */
    public static function modules(): array
    {
        return [
            'fct' => [
                'title' => 'FCT',
                'profiles' => [
                    'admin' => ['label' => 'Administración del centro', 'user' => 'alfa.admin'],
                    'jefe' => ['label' => 'Jefatura de departamento', 'user' => 'alfa.jefe'],
                    'tutor' => ['label' => 'Tutoría docente y de grupo', 'user' => 'alfa.tutor'],
                    'otro-tutor' => ['label' => 'Tutoría docente de otro acuerdo', 'user' => 'alfa.profesor'],
                    'alumno' => ['label' => 'Alumnado del acuerdo', 'user' => 'alfa.alumno1'],
                    'tutor-laboral' => ['label' => 'Tutoría laboral del acuerdo', 'user' => 'alfa.tutorlaboral'],
                    'economico' => ['label' => 'Responsable económico', 'user' => 'alfa.economico'],
                    'resto' => ['label' => 'Resto del personal', 'user' => 'alfa.gestorffe'],
                ],
                'sections' => [
                    self::section('Convocatorias y actividades', [
                        ['Ver el listado de convocatorias', 'GET /fct/convocatoria/listar/{year}/1'],
                        ['Crear una convocatoria', 'GET /fct/convocatoria/nueva/{year}'],
                        ['Editar una convocatoria', 'GET /fct/convocatoria/{shift}'],
                        ['Eliminar convocatorias', 'POST /fct/convocatoria/eliminar/{year} items[]={shift}'],
                        ['Ver las actividades de una convocatoria', 'GET /fct/actividad/{shift}/listar/1'],
                        ['Crear una actividad', 'GET /fct/actividad/nueva/{shift}'],
                        ['Copiar actividades de otra convocatoria', 'GET /fct/actividad/copiar/{shift}'],
                        ['Copiar actividades de un proyecto de FP dual', 'GET /fct/actividad/copiar/dual/{shift}'],
                        ['Importar actividades desde texto', 'POST /fct/actividad/importar/{shift}'],
                        ['Eliminar actividades', 'POST /fct/actividad/operacion/{shift} items[]={activity}'],
                    ]),
                    self::section('Acuerdos', [
                        ['Ver los acuerdos de una convocatoria', 'GET /fct/acuerdo/{shift}/listar/1'],
                        ['Crear un acuerdo', 'GET /fct/acuerdo/nuevo/{shift}'],
                        ['Editar un acuerdo', 'GET /fct/acuerdo/{agreement}'],
                        ['Gestionar el alumnado de un acuerdo', 'GET /fct/acuerdo/estudiante/{enrollment}'],
                        ['Descargar el programa formativo de un acuerdo', 'GET /fct/acuerdo/programa/descargar/{agreement}'],
                        ['Eliminar acuerdos', 'POST /fct/acuerdo/operacion/{shift} items[]={agreement}'],
                        ['Ver el calendario de un acuerdo', 'GET /fct/convenio/calendario/{agreement}'],
                    ]),
                    self::section('Seguimiento diario', [
                        ['Ver el seguimiento del alumnado', 'GET /fct/seguimiento/acuerdo/listar/{year}/1'],
                        ['Abrir el calendario de seguimiento de un alumno', 'GET /fct/seguimiento/calendario/{enrollment}'],
                        ['Descargar la hoja de asistencia', 'GET /fct/seguimiento/calendario/{enrollment}/asistencia/descargar'],
                        ['Descargar el resumen de actividades', 'GET /fct/seguimiento/calendario/{enrollment}/actividades/descargar'],
                        ['Ver los informes finales', 'GET /fct/informe/acuerdo/{year}/1'],
                        ['Abrir el informe final para cumplimentarlo', 'GET /fct/informe/cumplimentar/{enrollment}'],
                        ['Descargar el informe final', 'GET /fct/informe/descargar/{enrollment}', self::NOT_FOUND_IS_ALLOWED],
                    ]),
                    self::section('Visitas', [
                        ['Ver el resumen de visitas del profesorado', 'GET /fct/visita/resumen/{year}/1'],
                        ['Ver las visitas de un docente', 'GET /fct/visita/{teacher}/listar/1'],
                        ['Registrar una visita', 'GET /fct/visita/nueva/{teacher}'],
                        ['Descargar el informe de visitas', 'GET /fct/visita/{teacher}/descargar'],
                        ['Eliminar visitas', 'POST /fct/visita/eliminar/{teacher} items[]={visit}'],
                    ]),
                    self::section('Gastos de desplazamiento', [
                        ['Ver el resumen de gastos del profesorado', 'GET /fct/desplazamiento/resumen/{year}/1'],
                        ['Ver los gastos de un docente', 'GET /fct/desplazamiento/{teacher}/listar/1'],
                        ['Registrar un gasto', 'GET /fct/desplazamiento/nuevo/{teacher}'],
                        ['Descargar el informe de gastos', 'GET /fct/desplazamiento/{teacher}/descargar'],
                        ['Eliminar gastos', 'POST /fct/desplazamiento/eliminar/{teacher} items[]={expense}'],
                    ]),
                    self::section('Encuestas', [
                        ['Ver las encuestas de alumnado', 'GET /fct/encuesta/estudiante/{year}/1'],
                        ['Abrir la encuesta de alumnado de un acuerdo', 'GET /fct/encuesta/estudiante/cumplimentar/{enrollment}'],
                        ['Ver las encuestas de empresa', 'GET /fct/encuesta/empresa/{year}/1'],
                        ['Abrir la encuesta de empresa de un acuerdo', 'GET /fct/encuesta/empresa/cumplimentar/{enrollment}/{workTutor}'],
                        ['Ver las encuestas de centro', 'GET /fct/encuesta/centro/{year}/1'],
                        ['Abrir la encuesta de centro de un docente', 'GET /fct/encuesta/centro/cumplimentar/{shift}/{teacher}'],
                    ]),
                    self::section('Informes de la convocatoria', [
                        ['Acceder al apartado de informes', 'GET /fct/informe/'],
                        ['Ver el listado de informes de las encuestas', 'GET /fct/informe/encuesta/estudiantes/listar/{year}/1'],
                        ['Obtener el informe de la encuesta de alumnado', 'GET /fct/informe/encuesta/estudiantes/{shift}'],
                        ['Obtener el informe de la encuesta de empresas', 'GET /fct/informe/encuesta/empresas/{shift}'],
                        ['Obtener el informe de la encuesta de centro', 'GET /fct/informe/encuesta/centro/{shift}'],
                    ]),
                ],
            ],
        ];
    }

    /**
     * Marca de una acción cuya respuesta 404 significa «permitido»: el permiso se comprueba antes de buscar los
     * datos y las fixtures aún no los tienen (por ejemplo, el informe final de un alumno que no lo ha rellenado).
     * Quien no tiene permiso recibe un 403.
     */
    public const NOT_FOUND_IS_ALLOWED = 'not-found-is-allowed';

    /**
     * @param list<array{string, string}|array{string, string, string}> $actions etiqueta, petición («MÉTODO /ruta») y marca opcional
     * @return array{title: string, actions: list<array{label: string, request: string, notFoundIsAllowed?: bool}>}
     */
    private static function section(string $title, array $actions): array
    {
        return [
            'title' => $title,
            'actions' => array_map(static function (array $a): array {
                $action = ['label' => $a[0], 'request' => $a[1]];
                if (($a[2] ?? null) === self::NOT_FOUND_IS_ALLOWED) {
                    $action['notFoundIsAllowed'] = true;
                }

                return $action;
            }, $actions),
        ];
    }

    private function __construct()
    {
    }
}
