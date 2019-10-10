<?php

namespace AppBundle\Migrations;

use AppBundle\Entity\Edu\Training;
use AppBundle\Entity\Role;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190707224037 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // crear proyectos
        $workLinkedTrainings = $this->connection->createQueryBuilder()
            ->select('t.id')
            ->addSelect('t.name')
            ->addSelect('t.wlt_student_survey_id')
            ->addSelect('t.wlt_company_survey_id')
            ->addSelect('ay.description AS academic_year')
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
            $projectName = $workLinkedTraining['name'] . ' - ' . $workLinkedTraining['academic_year'];
            $managerId = null;
            $teacherId = null;
            foreach ($roles as $role) {
                $managerId = $role['person_id'];
                $this->connection->insert('wlt_project', [
                    'organization_id' => $workLinkedTraining['organization_id'],
                    'name' => $projectName,
                    'manager_id' => $managerId,
                    'student_survey_id' => $workLinkedTraining['wlt_student_survey_id'],
                    'company_survey_id' => $workLinkedTraining['wlt_company_survey_id'],
                    'manager_survey_id' => $workLinkedTraining['wlt_organization_survey_id'],
                    'academic_year_manager_survey_id' => $workLinkedTraining['wlt_organization_survey_id'],
                ]);
                $teacher = $this->connection->createQueryBuilder()
                    ->select('t.id')
                    ->from('edu_teacher', 't')
                    ->where('t.academic_year_id = :academic_year_id')
                    ->andWhere('t.person_id = :person_id')
                    ->setParameter('academic_year_id', $workLinkedTraining['academic_year_id'])
                    ->setParameter('person_id', $managerId)
                    ->setMaxResults(1)
                    ->execute()
                    ->fetchAll();
                $teacherId = $teacher[0]['id'];
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

            if (!$this->connection->createQueryBuilder()
                ->select('et.id')
                ->from('wlt_educational_tutor', 'et')
                ->where('et.project_id = :project_id')
                ->andWhere('et.teacher_id = :teacher_id')
                ->setParameter('project_id', $project[0]['id'])
                ->setParameter('teacher_id', $teacherId)
                ->execute()->fetch()
            ) {
                $this->connection->insert('wlt_educational_tutor', [
                    'project_id' => $project[0]['id'],
                    'teacher_id' => $teacherId
                ]);
            }

            $tutor = $this->connection->createQueryBuilder()
                ->select('et.id')
                ->from('wlt_educational_tutor', 'et')
                ->where('et.project_id = :project_id')
                ->andWhere('et.teacher_id = :teacher_id')
                ->setParameter('project_id', $project[0]['id'])
                ->setParameter('teacher_id', $teacherId)
                ->setMaxResults(1)
                ->execute()
                ->fetchAll();

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
                $this->connection->createQueryBuilder()
                    ->update('wlt_agreement', 'a')
                    ->set('a.educational_tutor_id', $tutor[0]['id'])
                    ->set('a.project_id', $project[0]['id'])
                    ->where('a.student_enrollment_id = ' . $student['id'])
                    ->execute();

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
        $this->addSql('ALTER TABLE wlt_agreement MODIFY project_id INT NOT NULL, MODIFY educational_tutor_id INT NOT NULL');

        $this->addSql('ALTER TABLE wlt_agreement ADD CONSTRAINT FK_2B23AFE9166D1F9C FOREIGN KEY (project_id) REFERENCES wlt_project (id)');
        $this->addSql('ALTER TABLE wlt_agreement ADD CONSTRAINT FK_2B23AFE9E7F72E80 FOREIGN KEY (educational_tutor_id) REFERENCES wlt_educational_tutor (id)');
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

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->throwIrreversibleMigrationException("Sorry! Cannot downgrade to 1.x");
    }
}
