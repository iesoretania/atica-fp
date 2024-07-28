<?php

namespace App\Migrations;

use App\Entity\Edu\Training;
use App\Entity\Role;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190707224037 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // crear proyectos
        $workLinkedTrainings = $this->connection->createQueryBuilder()
            ->select('t.id')
            ->addSelect('t.name')
            ->addSelect('t.wlt_student_survey_id')
            ->addSelect('t.wlt_company_survey_id')
            ->addSelect('ay.start_date AS start_date')
            ->addSelect('ay.end_date AS end_date')
            ->addSelect('ay.id AS academic_year_id')
            ->addSelect('o.id AS organization_id')
            ->addSelect('ay.wlt_organization_survey_id AS wlt_organization_survey_id')
            ->from('edu_training', 't')
            ->where('t.work_linked = :status')
            ->join('t', 'edu_academic_year', 'ay', 't.academic_year_id = ay.id')
            ->join('ay', 'organization', 'o', 'ay.organization_id = o.id')
            ->setParameter('status', true)
            ->execute();

        // por cada enseñanza y curso académico...
        /** @var Training $workLinkedTraining */
        foreach ($workLinkedTrainings as $workLinkedTraining) {
            // obtener un coordinador/a de FP dual de la organización (será quien coordine el proyecto)
            $roles = $this->connection->createQueryBuilder()
                ->select('r.person_id')
                ->from('role', 'r')
                ->where('r.organization_id = :organization_id')
                ->andWhere('r.role = :role')
                ->setParameter('organization_id', $workLinkedTraining['organization_id'])
                ->setParameter('role', Role::ROLE_WLT_MANAGER)
                ->setMaxResults(1)
                ->execute();

            // crear el proyecto
            $startDate = new \DateTime($workLinkedTraining['start_date']);
            $startYear = $startDate->format('Y');
            $endDate = new \DateTime($workLinkedTraining['end_date']);
            $endYear = $endDate->format('Y');
            $projectName = $workLinkedTraining['name'] .
                ' - ' .
                $startYear .
                '-' .
                ((int) $endYear + 1);

            $managerId = null;
            $teacherId = null;
            $teacherAnsweredSurveyId = null;
            foreach ($roles as $role) {
                $managerId = $role['person_id'];
                $this->connection->insert('wlt_project', [
                    'organization_id' => $workLinkedTraining['organization_id'],
                    'name' => $projectName,
                    'manager_id' => $managerId,
                    'student_survey_id' => $workLinkedTraining['wlt_student_survey_id'],
                    'company_survey_id' => $workLinkedTraining['wlt_company_survey_id'],
                    'educational_tutor_survey_id' => $workLinkedTraining['wlt_organization_survey_id']
                ]);
                $teacher = $this->connection->createQueryBuilder()
                    ->select('t.id')
                    ->addSelect('t.wlt_teacher_survey_id')
                    ->from('edu_teacher', 't')
                    ->where('t.academic_year_id = :academic_year_id')
                    ->andWhere('t.person_id = :person_id')
                    ->setParameter('academic_year_id', $workLinkedTraining['academic_year_id'])
                    ->setParameter('person_id', $managerId)
                    ->setMaxResults(1)
                    ->execute()
                    ->fetchAll();
                $teacherId = $teacher[0]['id'];
                $teacherAnsweredSurveyId = $teacher[0]['wlt_teacher_survey_id'];
            }

            $project = $this->connection->createQueryBuilder()
                ->select('p.id')
                ->from('wlt_project', 'p')
                ->where('p.organization_id = :organization_id')
                ->andWhere('p.name = :name')
                ->setParameter('organization_id', $workLinkedTraining['organization_id'])
                ->setParameter('name', $projectName)
                ->setMaxResults(1)
                ->execute()
                ->fetchAll();

            if ($teacherAnsweredSurveyId) {
                $this->connection->insert('wlt_educational_tutor_answered_survey', [
                    'teacher_id' => $teacherId,
                    'project_id' => $project[0]['id'],
                    'answered_survey_id' => $teacherAnsweredSurveyId
                ]);
            }

            $subjects = $this->connection->createQueryBuilder()
                ->select('s.id')
                ->from('edu_subject', 's')
                ->join('s', 'edu_grade', 'g', 'g.id = s.grade_id')
                ->where('g.training_id = :training_id')
                ->setParameter('training_id', $workLinkedTraining['id'])
                ->execute();

            $this->connection->createQueryBuilder()
                ->update('wlt_learning_program', 'lp')
                ->set('lp.project_id', $project[0]['id'])
                ->where('lp.training_id = ' . $workLinkedTraining['id'])
                ->execute();

            foreach ($subjects as $subject) {
                $this->connection->createQueryBuilder()
                    ->update('wlt_activity', 'a')
                    ->set('a.project_id', $project[0]['id'])
                    ->where('a.subject_id = :subject_id')
                    ->setParameter('subject_id', $subject['id'])
                    ->execute();
            }

            // buscar acuerdos de colaboración y añadirles el proyecto tutor/a de seguimiento
            $students = $this->connection->createQueryBuilder()
                ->select('se.id')
                ->addSelect('se.group_id')
                ->from('edu_student_enrollment', 'se')
                ->join('se', 'edu_group', 'g', 'se.group_id = g.id')
                ->join('g', 'edu_grade', 'gr', 'g.grade_id = gr.id')
                ->where('gr.training_id = :training_id')
                ->setParameter('training_id', $workLinkedTraining['id'])
                ->execute();

            foreach ($students as $student) {
                $rows = $this->connection->createQueryBuilder()
                    ->update('wlt_agreement', 'a')
                    ->set('a.educational_tutor_id', $teacherId)
                    ->set('a.project_id', $project[0]['id'])
                    ->where('a.student_enrollment_id = ' . $student['id'])
                    ->execute();

                if ($rows > 0) {

                    // actualizar reuniones de los estudiantes
                    $this->connection->createQueryBuilder()
                        ->update('wlt_meeting', 'm')
                        ->set('m.project_id', $project[0]['id'])
                        ->where('m.id IN (SELECT mse.meeting_id FROM wlt_meeting_student_enrollment mse WHERE mse.student_enrollment_id = ' . $student['id'] . ')')
                        ->execute();

                    $this->connection->insert('wlt_project_student_enrollment', [
                        'project_id' => $project[0]['id'],
                        'student_enrollment_id' => $student['id']
                    ]);

                    if (!$this->connection->createQueryBuilder()
                        ->select('pg.project_id')
                        ->from('wlt_project_group', 'pg')
                        ->where('pg.project_id = :project_id')
                        ->andWhere('pg.group_id = :group_id')
                        ->setParameter('project_id', $project[0]['id'])
                        ->setParameter('group_id', $student['group_id'])
                        ->execute()->fetch()
                    ) {
                        $this->connection->insert('wlt_project_group', [
                            'project_id' => $project[0]['id'],
                            'group_id' => $student['group_id']
                        ]);
                    }
                }
            }
        }

        $this->addSql('ALTER TABLE wlt_agreement MODIFY project_id INT NOT NULL, MODIFY educational_tutor_id INT NOT NULL');

        $this->addSql('ALTER TABLE wlt_agreement ADD CONSTRAINT FK_2B23AFE9166D1F9C FOREIGN KEY (project_id) REFERENCES wlt_project (id)');
        $this->addSql('ALTER TABLE wlt_agreement ADD CONSTRAINT FK_2B23AFE9E7F72E80 FOREIGN KEY (educational_tutor_id) REFERENCES edu_teacher (id)');
        $this->addSql('CREATE INDEX IDX_2B23AFE9166D1F9C ON wlt_agreement (project_id)');
        $this->addSql('CREATE INDEX IDX_2B23AFE9E7F72E80 ON wlt_agreement (educational_tutor_id)');
        $this->addSql('ALTER TABLE edu_teacher DROP FOREIGN KEY FK_89A031C7F913D1F');
        $this->addSql('DROP INDEX IDX_89A031C7F913D1F ON edu_teacher');
        $this->addSql('ALTER TABLE edu_teacher DROP wlt_teacher_survey_id, DROP wlt_educational_tutor');
        $this->addSql('ALTER TABLE edu_subject DROP workplace_training');
        $this->addSql('ALTER TABLE edu_training DROP FOREIGN KEY FK_2692AD501294B084');
        $this->addSql('ALTER TABLE edu_training DROP FOREIGN KEY FK_2692AD5046E1FBF4');
        $this->addSql('DROP INDEX IDX_2692AD501294B084 ON edu_training');
        $this->addSql('DROP INDEX IDX_2692AD5046E1FBF4 ON edu_training');
        $this->addSql('ALTER TABLE edu_training DROP wlt_student_survey_id, DROP wlt_company_survey_id, DROP work_linked');
        $this->addSql('ALTER TABLE edu_teaching DROP work_linked');
        $this->addSql('ALTER TABLE edu_academic_year DROP FOREIGN KEY FK_CFBE31D0D5F8DFB5');
        $this->addSql('DROP INDEX IDX_CFBE31D0D5F8DFB5 ON edu_academic_year');
        $this->addSql('ALTER TABLE edu_academic_year DROP wlt_organization_survey_id');
        $this->addSql('ALTER TABLE edu_academic_year_audit DROP wlt_organization_survey_id');

        $this->addSql('ALTER TABLE wlt_learning_program DROP training_id');
        $this->addSql('ALTER TABLE wlt_learning_program MODIFY project_id INT NOT NULL');

        $this->addSql('ALTER TABLE wlt_activity MODIFY project_id INT NOT NULL');
        $this->addSql('ALTER TABLE wlt_activity DROP subject_id');

        $this->addSql('ALTER TABLE wlt_meeting MODIFY project_id INT NOT NULL');
        $this->addSql('ALTER TABLE wlt_meeting DROP academic_year_id');
        $this->addSql('ALTER TABLE wlt_meeting ADD CONSTRAINT FK_3E755F96166D1F9C FOREIGN KEY (project_id) REFERENCES wlt_project (id)');
        $this->addSql('CREATE INDEX IDX_3E755F96166D1F9C ON wlt_meeting (project_id)');
        $this->addSql('ALTER TABLE wlt_meeting_audit DROP academic_year_id');
        $this->addSql('ALTER TABLE wlt_meeting_audit ADD project_id INT DEFAULT NULL');

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->throwIrreversibleMigrationException("Sorry! Cannot downgrade to 1.x");
    }
}
