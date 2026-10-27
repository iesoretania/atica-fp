<?php

namespace App\Tests\Functional;

/**
 * Resuelve los parámetros de URL de una ruta con datos de una organización concreta, para los tests
 * que recorren muchas rutas. Requiere $this->em y getOrganization() de AppWebTestCase.
 */
trait ResolvesUrlParametersTrait
{
    /**
     * Parámetros de URL de una ruta con los datos de la organización indicada. Las variables que no se
     * saben resolver se prueban con los identificadores 1..$maxId.
     *
     * @param list<string> $variables
     * @return list<array<string, int|string>> posibles combinaciones de parámetros
     */
    private function urlParameters(array $variables, string $organizationCode, int $maxId): array
    {
        $organization = $this->getOrganization($organizationCode);
        $connection = $this->em->getConnection();
        $firstId = fn (string $sql): ?int => ($id = $connection->fetchOne($sql, [$organization->getId()])) === false ? null : (int) $id;

        $known = [
            'academicYear' => $organization->getCurrentAcademicYear()->getId(),
            'shift' => $firstId('SELECT s.id FROM wpt_shift s JOIN edu_subject su ON su.id = s.subject_id JOIN edu_grade g ON g.id = su.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year a ON a.id = t.academic_year_id WHERE a.organization_id = ? LIMIT 1'),
            'project' => $firstId('SELECT id FROM wlt_project WHERE organization_id = ? LIMIT 1'),
            'trainingProgram' => $firstId('SELECT tp.id FROM itp_training_program tp JOIN itp_program_grade pg ON pg.training_program_id = tp.id JOIN edu_grade g ON g.id = pg.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year a ON a.id = t.academic_year_id WHERE a.organization_id = ? LIMIT 1'),
            'programGrade' => $firstId('SELECT pg.id FROM itp_program_grade pg JOIN edu_grade g ON g.id = pg.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year a ON a.id = t.academic_year_id WHERE a.organization_id = ? LIMIT 1'),
            'programGroup' => $firstId('SELECT pgr.id FROM itp_program_group pgr JOIN itp_program_grade pg ON pg.id = pgr.program_grade_id JOIN edu_grade g ON g.id = pg.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year a ON a.id = t.academic_year_id WHERE a.organization_id = ? LIMIT 1'),
            'studentProgram' => $firstId('SELECT sp.id FROM itp_student_program sp JOIN itp_program_group pgr ON pgr.id = sp.program_group_id JOIN itp_program_grade pg ON pg.id = pgr.program_grade_id JOIN edu_grade g ON g.id = pg.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year a ON a.id = t.academic_year_id WHERE a.organization_id = ? LIMIT 1'),
            'studentProgramWorkcenter' => $firstId('SELECT spw.id FROM itp_student_program_workcenter spw JOIN itp_student_program sp ON sp.id = spw.student_program_id JOIN itp_program_group pgr ON pgr.id = sp.program_group_id JOIN itp_program_grade pg ON pg.id = pgr.program_grade_id JOIN edu_grade g ON g.id = pg.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year a ON a.id = t.academic_year_id WHERE a.organization_id = ? LIMIT 1'),
        ];

        $combinations = [[]];
        foreach ($variables as $variable) {
            $candidates = $known[$variable] ?? false;
            $candidates = $candidates === false ? range(1, $maxId) : [$candidates];
            if ($candidates === [null]) {
                return [];
            }
            $next = [];
            foreach ($combinations as $combination) {
                foreach ($candidates as $candidate) {
                    $next[] = $combination + [$variable => $candidate];
                }
            }
            $combinations = $next;
        }

        return $combinations;
    }
}
