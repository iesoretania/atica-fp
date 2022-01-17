<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200613200942 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE wlt_manager_answered_survey');
        $this->addSql('DROP TABLE wpt_report_audit');
        $this->addSql('ALTER TABLE wlt_project DROP FOREIGN KEY FK_E4D36E41D7568059');
        $this->addSql('ALTER TABLE wlt_project DROP FOREIGN KEY FK_E4D36E41F9D59FA4');
        $this->addSql('DROP INDEX IDX_E4D36E41F9D59FA4 ON wlt_project');
        $this->addSql('DROP INDEX IDX_E4D36E41D7568059 ON wlt_project');
        $this->addSql('ALTER TABLE wlt_project DROP manager_final_survey_id, DROP manager_survey_id');
        $this->addSql('ALTER TABLE wlt_project_audit DROP manager_survey_id, DROP manager_final_survey_id');
        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey DROP FOREIGN KEY FK_CBD11B75C54F3401');
        $this->addSql('DROP INDEX IDX_CBD11B75C54F3401 ON wlt_educational_tutor_answered_survey');
        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey DROP academic_year_id');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE wlt_manager_answered_survey (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, answered_survey_id INT NOT NULL, academic_year_id INT NOT NULL, INDEX IDX_FA50ACDC166D1F9C (project_id), INDEX IDX_FA50ACDCC54F3401 (academic_year_id), INDEX IDX_FA50ACDCA97283E6 (answered_survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_report_audit (agreement_enrollment_id INT NOT NULL, rev INT NOT NULL, work_activities LONGTEXT DEFAULT NULL COLLATE utf8mb4_spanish_ci, professional_competence INT DEFAULT NULL, organizational_competence INT DEFAULT NULL, relational_competence INT DEFAULT NULL, contingency_response INT DEFAULT NULL, other_description1 VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_spanish_ci, other1 INT DEFAULT NULL, other_description2 VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_spanish_ci, other2 INT DEFAULT NULL, proposed_changes LONGTEXT DEFAULT NULL COLLATE utf8mb4_spanish_ci, sign_date DATE DEFAULT NULL, revtype VARCHAR(4) NOT NULL COLLATE utf8mb4_spanish_ci, INDEX rev_fd82d0cd772f0fbe220033a10b2261ae_idx (rev), PRIMARY KEY(agreement_enrollment_id, rev)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wlt_manager_answered_survey ADD CONSTRAINT FK_FA50ACDC166D1F9C FOREIGN KEY (project_id) REFERENCES wlt_project (id)');
        $this->addSql('ALTER TABLE wlt_manager_answered_survey ADD CONSTRAINT FK_FA50ACDCA97283E6 FOREIGN KEY (answered_survey_id) REFERENCES answered_survey (id)');
        $this->addSql('ALTER TABLE wlt_manager_answered_survey ADD CONSTRAINT FK_FA50ACDCC54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey ADD academic_year_id INT NOT NULL');
        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey ADD CONSTRAINT FK_CBD11B75C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('CREATE INDEX IDX_CBD11B75C54F3401 ON wlt_educational_tutor_answered_survey (academic_year_id)');
        $this->addSql('ALTER TABLE wlt_project ADD manager_final_survey_id INT DEFAULT NULL, ADD manager_survey_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wlt_project ADD CONSTRAINT FK_E4D36E41D7568059 FOREIGN KEY (manager_final_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE wlt_project ADD CONSTRAINT FK_E4D36E41F9D59FA4 FOREIGN KEY (manager_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_E4D36E41F9D59FA4 ON wlt_project (manager_survey_id)');
        $this->addSql('CREATE INDEX IDX_E4D36E41D7568059 ON wlt_project (manager_final_survey_id)');
        $this->addSql('ALTER TABLE wlt_project_audit ADD manager_survey_id INT DEFAULT NULL, ADD manager_final_survey_id INT DEFAULT NULL');
    }
}
