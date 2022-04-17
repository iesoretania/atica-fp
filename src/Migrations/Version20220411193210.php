<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220411193210 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE wlt_student_answered_survey (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, student_enrollment_id INT NOT NULL, answered_survey_id INT NOT NULL, INDEX IDX_E4FA2DBC166D1F9C (project_id), INDEX IDX_E4FA2DBCDAE14AC5 (student_enrollment_id), INDEX IDX_E4FA2DBCA97283E6 (answered_survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_work_tutor_answered_survey (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, academic_year_id INT NOT NULL, work_tutor_id INT NOT NULL, answered_survey_id INT NOT NULL, INDEX IDX_B4644BE3166D1F9C (project_id), INDEX IDX_B4644BE3C54F3401 (academic_year_id), INDEX IDX_B4644BE3F53AEEAD (work_tutor_id), INDEX IDX_B4644BE3A97283E6 (answered_survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wlt_student_answered_survey ADD CONSTRAINT FK_E4FA2DBC166D1F9C FOREIGN KEY (project_id) REFERENCES wlt_project (id)');
        $this->addSql('ALTER TABLE wlt_student_answered_survey ADD CONSTRAINT FK_E4FA2DBCDAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id)');
        $this->addSql('ALTER TABLE wlt_student_answered_survey ADD CONSTRAINT FK_E4FA2DBCA97283E6 FOREIGN KEY (answered_survey_id) REFERENCES answered_survey (id)');
        $this->addSql('ALTER TABLE wlt_work_tutor_answered_survey ADD CONSTRAINT FK_B4644BE3166D1F9C FOREIGN KEY (project_id) REFERENCES wlt_project (id)');
        $this->addSql('ALTER TABLE wlt_work_tutor_answered_survey ADD CONSTRAINT FK_B4644BE3C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE wlt_work_tutor_answered_survey ADD CONSTRAINT FK_B4644BE3F53AEEAD FOREIGN KEY (work_tutor_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE wlt_work_tutor_answered_survey ADD CONSTRAINT FK_B4644BE3A97283E6 FOREIGN KEY (answered_survey_id) REFERENCES answered_survey (id)');

        $this->addSql(<<<'EOF'
INSERT INTO wlt_student_answered_survey (project_id, student_enrollment_id, answered_survey_id)
SELECT a.project_id, a.student_enrollment_id, a.student_survey_id
FROM wlt_agreement a
WHERE a.student_survey_id IS NOT NULL LIMIT 1
EOF
        );
        $this->addSql(<<<'EOF'
INSERT INTO wlt_work_tutor_answered_survey (project_id, academic_year_id, work_tutor_id, answered_survey_id)
SELECT a.project_id, et.academic_year_id, a.work_tutor_id, a.company_survey_id
FROM wlt_agreement a
         JOIN edu_student_enrollment ese on a.student_enrollment_id = ese.id
         JOIN edu_group eg on ese.group_id = eg.id
         JOIN edu_grade e on eg.grade_id = e.id
         JOIN edu_training et on e.training_id = et.id
WHERE a.company_survey_id IS NOT NULL LIMIT 1
EOF
        );
        $this->addSql('ALTER TABLE wlt_agreement DROP FOREIGN KEY FK_2B23AFE980E5DA6D');
        $this->addSql('ALTER TABLE wlt_agreement DROP FOREIGN KEY FK_2B23AFE9D490911D');
        $this->addSql('DROP INDEX IDX_2B23AFE980E5DA6D ON wlt_agreement');
        $this->addSql('DROP INDEX IDX_2B23AFE9D490911D ON wlt_agreement');
        $this->addSql('ALTER TABLE wlt_agreement DROP student_survey_id, DROP company_survey_id');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException("Sorry! Cannot downgrade.");
    }
}
