<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190707224036 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE wlt_project (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, manager_id INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_E4D36E4132C8A3DE (organization_id), INDEX IDX_E4D36E41783E3463 (manager_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_project_group (project_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_9FF4D6B7166D1F9C (project_id), INDEX IDX_9FF4D6B7FE54D947 (group_id), PRIMARY KEY(project_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_project_student_enrollment (project_id INT NOT NULL, student_enrollment_id INT NOT NULL, INDEX IDX_E0458B76166D1F9C (project_id), INDEX IDX_E0458B76DAE14AC5 (student_enrollment_id), PRIMARY KEY(project_id, student_enrollment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wlt_project ADD CONSTRAINT FK_E4D36E4132C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE wlt_project ADD CONSTRAINT FK_E4D36E41783E3463 FOREIGN KEY (manager_id) REFERENCES person (id)');

        $this->addSql('ALTER TABLE wlt_project_group ADD CONSTRAINT FK_9FF4D6B7166D1F9C FOREIGN KEY (project_id) REFERENCES wlt_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_project_group ADD CONSTRAINT FK_9FF4D6B7FE54D947 FOREIGN KEY (group_id) REFERENCES edu_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_project_student_enrollment ADD CONSTRAINT FK_E0458B76166D1F9C FOREIGN KEY (project_id) REFERENCES wlt_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_project_student_enrollment ADD CONSTRAINT FK_E0458B76DAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE wlt_agreement ADD project_id INT NULL, ADD educational_tutor_id INT NULL');

        $this->addSql('ALTER TABLE wlt_project ADD student_survey_id INT DEFAULT NULL, ADD company_survey_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wlt_project ADD CONSTRAINT FK_E4D36E41D490911D FOREIGN KEY (student_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE wlt_project ADD CONSTRAINT FK_E4D36E4180E5DA6D FOREIGN KEY (company_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_E4D36E41D490911D ON wlt_project (student_survey_id)');
        $this->addSql('CREATE INDEX IDX_E4D36E4180E5DA6D ON wlt_project (company_survey_id)');

        $this->addSql('ALTER TABLE wlt_project ADD manager_survey_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wlt_project ADD CONSTRAINT FK_E4D36E41F9D59FA4 FOREIGN KEY (manager_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_E4D36E41F9D59FA4 ON wlt_project (manager_survey_id)');

        $this->addSql('CREATE TABLE wlt_educational_tutor_answered_survey (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, academic_year_id INT NOT NULL, answered_survey_id INT NOT NULL, INDEX IDX_CBD11B75166D1F9C (project_id), INDEX IDX_CBD11B75C54F3401 (academic_year_id), INDEX IDX_CBD11B75A97283E6 (answered_survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_manager_answered_survey (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, academic_year_id INT NOT NULL, answered_survey_id INT NOT NULL, INDEX IDX_FA50ACDC166D1F9C (project_id), INDEX IDX_FA50ACDCC54F3401 (academic_year_id), INDEX IDX_FA50ACDCA97283E6 (answered_survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey ADD CONSTRAINT FK_CBD11B75166D1F9C FOREIGN KEY (project_id) REFERENCES wlt_project (id)');
        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey ADD CONSTRAINT FK_CBD11B75C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey ADD CONSTRAINT FK_CBD11B75A97283E6 FOREIGN KEY (answered_survey_id) REFERENCES answered_survey (id)');
        $this->addSql('ALTER TABLE wlt_manager_answered_survey ADD CONSTRAINT FK_FA50ACDC166D1F9C FOREIGN KEY (project_id) REFERENCES wlt_project (id)');
        $this->addSql('ALTER TABLE wlt_manager_answered_survey ADD CONSTRAINT FK_FA50ACDCC54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE wlt_manager_answered_survey ADD CONSTRAINT FK_FA50ACDCA97283E6 FOREIGN KEY (answered_survey_id) REFERENCES answered_survey (id)');

        $this->addSql('CREATE TABLE wlt_project_audit (id INT NOT NULL, rev INT NOT NULL, organization_id INT DEFAULT NULL, manager_id INT DEFAULT NULL, student_survey_id INT DEFAULT NULL, company_survey_id INT DEFAULT NULL, manager_survey_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_e55594229ab1a86fcf85548ae1e37a5e_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');

        $this->addSql('ALTER TABLE wlt_learning_program DROP FOREIGN KEY FK_5EB2E44DBEFD98D1');
        $this->addSql('DROP INDEX UNIQ_5EB2E44D979B1AD6BEFD98D1 ON wlt_learning_program');
        $this->addSql('DROP INDEX IDX_5EB2E44DBEFD98D1 ON wlt_learning_program');

        $this->addSql('ALTER TABLE wlt_learning_program ADD project_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wlt_learning_program ADD CONSTRAINT FK_5EB2E44D166D1F9C FOREIGN KEY (project_id) REFERENCES wlt_project (id)');
        $this->addSql('CREATE INDEX IDX_5EB2E44D166D1F9C ON wlt_learning_program (project_id)');
        $this->addSql('ALTER TABLE wlt_learning_program_audit DROP training_id');
        $this->addSql('ALTER TABLE wlt_learning_program_audit ADD project_id INT DEFAULT NULL');

        $this->addSql('ALTER TABLE wlt_activity DROP FOREIGN KEY FK_EAD6D79D23EDC87');
        $this->addSql('DROP INDEX UNIQ_EAD6D79D23EDC8777153098 ON wlt_activity');
        $this->addSql('DROP INDEX IDX_EAD6D79D23EDC87 ON wlt_activity');
        $this->addSql('ALTER TABLE wlt_activity ADD project_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wlt_activity ADD CONSTRAINT FK_EAD6D79D166D1F9C FOREIGN KEY (project_id) REFERENCES wlt_project (id)');
        $this->addSql('CREATE INDEX IDX_EAD6D79D166D1F9C ON wlt_activity (project_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EAD6D79D166D1F9C77153098 ON wlt_activity (project_id, code)');

        $this->addSql('ALTER TABLE wlt_meeting DROP FOREIGN KEY FK_3E755F96C54F3401');
        $this->addSql('DROP INDEX IDX_3E755F96C54F3401 ON wlt_meeting');
        $this->addSql('ALTER TABLE wlt_meeting ADD project_id INT DEFAULT NULL');

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
