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
            'general' => [
                'title' => 'Parte general',
                'profiles' => [
                    'plataforma' => ['label' => 'Administración de la plataforma', 'user' => 'admin'],
                    'admin' => ['label' => 'Administración del centro', 'user' => 'alfa.admin'],
                    'economico' => ['label' => 'Responsable económico', 'user' => 'alfa.economico'],
                    'jefe' => ['label' => 'Jefatura de departamento', 'user' => 'alfa.jefe'],
                    'coordinador' => ['label' => 'Coordinación de FP dual', 'user' => 'alfa.coordinador'],
                    'gestor-ffe' => ['label' => 'Gestión de la FFE', 'user' => 'alfa.gestorffe'],
                    'profesorado' => ['label' => 'Profesorado sin otro cargo', 'user' => 'alfa.profesor'],
                    'alumnado' => ['label' => 'Alumnado', 'user' => 'alfa.alumno1'],
                ],
                'sections' => [
                    self::section('Datos personales', [
                        ['Ver y cambiar sus datos personales y su contraseña', 'GET /datos'],
                    ]),
                    self::section('Plataforma', [
                        ['Acceder al apartado de administración', 'GET /admin'],
                        ['Ver el listado de centros', 'GET /admin/organizaciones/listar/1'],
                        ['Editar un centro', 'GET /admin/organizaciones/{organization}'],
                        ['Crear un centro', 'GET /admin/organizaciones/nueva'],
                        ['Ver el listado de usuarios', 'GET /admin/usuarios/listar/1'],
                        ['Editar un usuario', 'GET /admin/usuarios/{person}'],
                    ]),
                    self::section('Cursos académicos', [
                        ['Ver el listado de cursos académicos', 'GET /centro/cursoacademico/listar/1'],
                        ['Editar un curso académico', 'GET /centro/cursoacademico/{year}'],
                        ['Copiar datos de otro curso', 'GET /centro/cursoacademico/copiar/{year}'],
                    ]),
                    self::section('Gestión del centro', [
                        ['Acceder al apartado de gestión del centro', 'GET /centro'],
                        ['Ver el listado de profesorado', 'GET /centro/profesorado/listar/{year}/1'],
                        ['Editar un docente', 'GET /centro/profesorado/{teacher}'],
                        ['Eliminar docentes', 'POST /centro/profesorado/eliminar/{year} users[]={teacher}'],
                        ['Ver el listado de matrículas', 'GET /centro/matricula/listar/{year}/1'],
                        ['Editar una matrícula', 'GET /centro/matricula/{studentEnrollment}'],
                        ['Ver el listado de departamentos', 'GET /centro/departamento/listar/{year}/1'],
                        ['Editar un departamento', 'GET /centro/departamento/{department}'],
                        ['Ver el listado de enseñanzas', 'GET /centro/ensenanza/listar/{year}/1'],
                        ['Editar una enseñanza', 'GET /centro/ensenanza/{training}'],
                        ['Ver el listado de niveles', 'GET /centro/nivel/listar/{year}/1'],
                        ['Ver el listado de grupos', 'GET /centro/grupo/listar/{year}/1'],
                        ['Editar un grupo', 'GET /centro/grupo/{group}'],
                        ['Ver el listado de materias', 'GET /centro/materia/listar/{year}/1'],
                        ['Editar una materia', 'GET /centro/materia/{subject}'],
                        ['Ver el listado de días no lectivos', 'GET /centro/dia_no_lectivo/listar/{year}/1'],
                        ['Ver los tipos de contacto con empresas', 'GET /centro/contactos/listar/{year}/1'],
                        ['Asignar roles del centro', 'GET /centro/rol'],
                    ]),
                    self::section('Importaciones', [
                        ['Acceder al apartado de importaciones', 'GET /centro/importar'],
                        ['Importar profesorado', 'GET /centro/importar/profesorado'],
                        ['Importar grupos', 'GET /centro/importar/grupo'],
                        ['Importar alumnado', 'GET /centro/importar/estudiante'],
                    ]),
                    self::section('Empresas y centros de trabajo', [
                        ['Ver el listado de empresas', 'GET /empresa/listar/1'],
                        ['Crear una empresa', 'GET /empresa/nueva'],
                        ['Editar una empresa', 'GET /empresa/{company}'],
                        ['Eliminar empresas', 'POST /empresa/eliminar items[]={company}'],
                        ['Ver los centros de trabajo de una empresa', 'GET /empresa/{company}/sede/listar/1'],
                        ['Editar un centro de trabajo', 'GET /empresa/sede/{workcenter}'],
                    ]),
                    self::section('Encuestas y plantillas', [
                        ['Ver el listado de encuestas', 'GET /centro/encuesta/listar/1'],
                        ['Editar una encuesta', 'GET /centro/encuesta/{survey}'],
                        ['Ver las preguntas de una encuesta', 'GET /centro/encuesta/pregunta/listar/{survey}/resultado/1'],
                        ['Ver el listado de plantillas de informe', 'GET /centro/plantilla/listar/1'],
                        ['Editar una plantilla de informe', 'GET /centro/plantilla/{template}'],
                    ]),
                    self::section('Desplazamientos y valoración', [
                        ['Ver los itinerarios de desplazamiento', 'GET /centro/itinerario/listar/1'],
                        ['Editar un itinerario', 'GET /centro/itinerario/detalle/{route}'],
                        ['Ver las escalas de valoración', 'GET /centro/escala/listar/1/'],
                        ['Editar una escala de valoración', 'GET /centro/escala/{scale}'],
                    ]),
                ],
            ],
            'dual' => [
                'title' => 'FP dual',
                'profiles' => [
                    'admin' => ['label' => 'Administración del centro', 'user' => 'alfa.admin'],
                    'coordinador' => ['label' => 'Coordinación del proyecto', 'user' => 'alfa.coordinador'],
                    'jefe' => ['label' => 'Jefatura de departamento', 'user' => 'alfa.jefe'],
                    'tutor' => ['label' => 'Responsable de seguimiento y tutoría de grupo', 'user' => 'alfa.tutor'],
                    'otro-tutor' => ['label' => 'Responsable de seguimiento de otro convenio', 'user' => 'alfa.profesor'],
                    'profesorado' => ['label' => 'Profesorado del grupo', 'user' => 'alfa.director'],
                    'alumno' => ['label' => 'Alumnado del convenio', 'user' => 'alfa.alumno5'],
                    'tutor-laboral' => ['label' => 'Responsable laboral del convenio', 'user' => 'alfa.tutorlaboral'],
                    'economico' => ['label' => 'Responsable económico', 'user' => 'alfa.economico'],
                    'resto' => ['label' => 'Resto del personal', 'user' => 'alfa.gestorffe'],
                ],
                'sections' => [
                    self::section('Proyectos', [
                        ['Ver el listado de proyectos', 'GET /dual/proyecto/listar/{year}/1'],
                        ['Crear un proyecto', 'GET /dual/proyecto/nuevo'],
                        ['Editar un proyecto', 'GET /dual/proyecto/{project}'],
                        ['Gestionar el alumnado de un proyecto', 'GET /dual/proyecto/estudiantes/{project}'],
                        ['Eliminar proyectos', 'POST /dual/proyecto/eliminar items[]={project}'],
                    ]),
                    self::section('Programa formativo', [
                        ['Ver las empresas de un proyecto', 'GET /dual/programa/{project}/listar/1'],
                        ['Incorporar una empresa al proyecto', 'GET /dual/programa/nuevo/{project}'],
                        ['Editar el programa de una empresa', 'GET /dual/programa/{learningProgram}'],
                        ['Eliminar empresas del proyecto', 'POST /dual/programa/operacion/{project} items[]={learningProgram}'],
                        ['Ver las actividades de un proyecto', 'GET /dual/proyecto/programa/{project}/actividad/1'],
                        ['Crear una actividad', 'GET /dual/proyecto/programa/{project}/actividad/nueva'],
                        ['Editar una actividad', 'GET /dual/proyecto/programa/actividad/{dualActivity}'],
                        ['Copiar actividades de otro proyecto', 'GET /dual/proyecto/copiar/{project}'],
                        ['Eliminar actividades', 'POST /dual/proyecto/programa/{project}/actividad/eliminar items[]={dualActivity}'],
                        ['Ver las concreciones de una actividad', 'GET /dual/proyecto/programa/{dualActivity}/concrecion/1'],
                        ['Editar una concreción', 'GET /dual/proyecto/programa/{realization}/detalles/concrecion'],
                    ]),
                    self::section('Convenios', [
                        ['Ver los convenios de un proyecto', 'GET /dual/acuerdo/{project}/listar/1'],
                        ['Crear un convenio', 'GET /dual/acuerdo/nuevo/{project}'],
                        ['Editar un convenio', 'GET /dual/acuerdo/{dualAgreement}'],
                        ['Ver el calendario de un convenio', 'GET /dual/acuerdo/calendario/{dualAgreement}'],
                        ['Eliminar convenios', 'POST /dual/acuerdo/operacion/{project} items[]={dualAgreement}'],
                    ]),
                    self::section('Seguimiento diario', [
                        ['Ver el seguimiento del alumnado', 'GET /dual/seguimiento/acuerdo/listar/{year}/1'],
                        ['Abrir el calendario de seguimiento de un alumno', 'GET /dual/seguimiento/calendario/{dualAgreement}'],
                        ['Descargar la hoja de asistencia', 'GET /dual/seguimiento/calendario/{dualAgreement}/asistencia'],
                    ]),
                    self::section('Valoración y evaluación', [
                        ['Ver los convenios evaluables', 'GET /dual/convenio/evaluar/listar/{year}/1'],
                        ['Abrir la valoración de actividades de un convenio (consulta o modificación según el perfil)', 'GET /dual/convenio/evaluar/{dualAgreement}'],
                        ['Ver el resumen de evaluación', 'GET /dual/resultado/estudiante/listar/{year}/1'],
                        ['Obtener el informe de resultados de un alumno', 'GET /dual/resultado/estudiante/{dualStudent}'],
                    ]),
                    self::section('Reuniones de tutorización', [
                        ['Ver las reuniones', 'GET /dual/reunion/listar/{year}/1'],
                        ['Registrar una reunión', 'GET /dual/reunion/nueva/{year}'],
                        ['Editar una reunión', 'GET /dual/reunion/{meeting}'],
                        ['Eliminar reuniones', 'POST /dual/reunion/eliminar items[]={meeting}'],
                    ]),
                    self::section('Contactos con empresas', [
                        ['Ver los contactos con empresas', 'GET /dual/contacto/listar/{year}/1'],
                        ['Registrar un contacto', 'GET /dual/contacto/nuevo'],
                        ['Editar un contacto', 'GET /dual/contacto/{dualContact}'],
                        ['Eliminar contactos', 'POST /dual/contacto/eliminar items[]={dualContact}'],
                        ['Ver el informe de contactos del responsable de seguimiento', 'GET /dual/contacto/informe/seguimiento/{year}/1'],
                        ['Ver el informe de contactos por centro de trabajo', 'GET /dual/contacto/informe/centros/{year}/1'],
                    ]),
                    self::section('Gastos de desplazamiento', [
                        ['Ver el resumen de gastos del profesorado', 'GET /dual/desplazamiento/resumen/{year}/1'],
                        ['Ver los gastos de un docente', 'GET /dual/desplazamiento/{teacher}/listar/1'],
                        ['Registrar un gasto', 'GET /dual/desplazamiento/nuevo/{teacher}'],
                        ['Descargar el informe de gastos', 'GET /dual/desplazamiento/{teacher}/descargar'],
                        ['Eliminar gastos', 'POST /dual/desplazamiento/eliminar/{teacher} items[]={dualExpense}'],
                    ]),
                    self::section('Encuestas', [
                        ['Ver las encuestas de alumnado', 'GET /dual/encuesta/estudiante/{year}/1'],
                        ['Abrir la encuesta de alumnado de un convenio', 'GET /dual/encuesta/estudiante/cumplimentar/{dualAgreement}'],
                        ['Ver las encuestas de empresa', 'GET /dual/encuesta/empresa/{year}/1'],
                        ['Abrir la encuesta de empresa de un convenio', 'GET /dual/encuesta/empresa/cumplimentar/{dualAgreement}/{workTutor}'],
                        ['Ver las encuestas de centro', 'GET /dual/encuesta/centro/{year}/1'],
                        ['Abrir la encuesta de centro de un docente', 'GET /dual/encuesta/centro/cumplimentar/{project}/{teacher}'],
                    ]),
                    self::section('Informes del proyecto', [
                        ['Acceder al apartado de informes', 'GET /dual/informe/'],
                        ['Obtener el informe de reuniones', 'GET /dual/informe/reuniones/{project}/{year}'],
                        ['Obtener el informe de asistencia', 'GET /dual/informe/asistencia/{project}/{year}'],
                        ['Obtener el informe de evaluación', 'GET /dual/informe/evaluacion/{project}/{year}'],
                        ['Obtener el informe del programa formativo', 'GET /dual/informe/programa_formativo/{project}/{year}'],
                        ['Obtener el informe de la encuesta de alumnado', 'GET /dual/informe/encuesta/estudiantes/{project}/{year}'],
                        ['Obtener el informe de la encuesta de empresas', 'GET /dual/informe/encuesta/empresas/{project}/{year}'],
                        ['Obtener el informe de la encuesta de centro', 'GET /dual/informe/encuesta/centro/{project}/{year}'],
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
