<?php

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20191111175911 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_project ADD manager_final_survey_id INT DEFAULT NULL, ADD educational_tutor_survey_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wlt_project ADD CONSTRAINT FK_E4D36E41D7568059 FOREIGN KEY (manager_final_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE wlt_project ADD CONSTRAINT FK_E4D36E4168F6798B FOREIGN KEY (educational_tutor_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_E4D36E41D7568059 ON wlt_project (manager_final_survey_id)');
        $this->addSql('CREATE INDEX IDX_E4D36E4168F6798B ON wlt_project (educational_tutor_survey_id)');
        $this->addSql('ALTER TABLE wlt_project_audit ADD manager_final_survey_id INT DEFAULT NULL, ADD educational_tutor_survey_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey DROP FOREIGN KEY FK_CBD11B75C54F3401');
        $this->addSql('DROP INDEX IDX_CBD11B75C54F3401 ON wlt_educational_tutor_answered_survey');
        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey CHANGE academic_year_id teacher_id INT NOT NULL');
        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey ADD CONSTRAINT FK_CBD11B7541807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id)');
        $this->addSql('CREATE INDEX IDX_CBD11B7541807E1D ON wlt_educational_tutor_answered_survey (teacher_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey DROP FOREIGN KEY FK_CBD11B7541807E1D');
        $this->addSql('DROP INDEX IDX_CBD11B7541807E1D ON wlt_educational_tutor_answered_survey');
        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey CHANGE teacher_id academic_year_id INT NOT NULL');
        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey ADD CONSTRAINT FK_CBD11B75C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('CREATE INDEX IDX_CBD11B75C54F3401 ON wlt_educational_tutor_answered_survey (academic_year_id)');
        $this->addSql('ALTER TABLE wlt_project DROP FOREIGN KEY FK_E4D36E41D7568059');
        $this->addSql('ALTER TABLE wlt_project DROP FOREIGN KEY FK_E4D36E4168F6798B');
        $this->addSql('DROP INDEX IDX_E4D36E41D7568059 ON wlt_project');
        $this->addSql('DROP INDEX IDX_E4D36E4168F6798B ON wlt_project');
        $this->addSql('ALTER TABLE wlt_project DROP manager_final_survey_id, DROP educational_tutor_survey_id');
        $this->addSql('ALTER TABLE wlt_project_audit DROP manager_final_survey_id, DROP educational_tutor_survey_id');
    }
}
