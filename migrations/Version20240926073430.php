<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240926073430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_training_program ADD student_survey_id INT DEFAULT NULL, ADD company_survey_id INT DEFAULT NULL, ADD educational_tutor_survey_id INT DEFAULT NULL, ADD attendance_report_template_id INT DEFAULT NULL, ADD final_report_template_id INT DEFAULT NULL, ADD weekly_activity_report_template_id INT DEFAULT NULL, ADD activity_summary_report_template_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE itp_training_program ADD CONSTRAINT FK_AF5EDD7BD490911D FOREIGN KEY (student_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE itp_training_program ADD CONSTRAINT FK_AF5EDD7B80E5DA6D FOREIGN KEY (company_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE itp_training_program ADD CONSTRAINT FK_AF5EDD7B68F6798B FOREIGN KEY (educational_tutor_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE itp_training_program ADD CONSTRAINT FK_AF5EDD7B13E6472B FOREIGN KEY (attendance_report_template_id) REFERENCES edu_report_template (id)');
        $this->addSql('ALTER TABLE itp_training_program ADD CONSTRAINT FK_AF5EDD7B10BDE131 FOREIGN KEY (final_report_template_id) REFERENCES edu_report_template (id)');
        $this->addSql('ALTER TABLE itp_training_program ADD CONSTRAINT FK_AF5EDD7B3BBF8EEC FOREIGN KEY (weekly_activity_report_template_id) REFERENCES edu_report_template (id)');
        $this->addSql('ALTER TABLE itp_training_program ADD CONSTRAINT FK_AF5EDD7B7791D10 FOREIGN KEY (activity_summary_report_template_id) REFERENCES edu_report_template (id)');
        $this->addSql('CREATE INDEX IDX_AF5EDD7BD490911D ON itp_training_program (student_survey_id)');
        $this->addSql('CREATE INDEX IDX_AF5EDD7B80E5DA6D ON itp_training_program (company_survey_id)');
        $this->addSql('CREATE INDEX IDX_AF5EDD7B68F6798B ON itp_training_program (educational_tutor_survey_id)');
        $this->addSql('CREATE INDEX IDX_AF5EDD7B13E6472B ON itp_training_program (attendance_report_template_id)');
        $this->addSql('CREATE INDEX IDX_AF5EDD7B10BDE131 ON itp_training_program (final_report_template_id)');
        $this->addSql('CREATE INDEX IDX_AF5EDD7B3BBF8EEC ON itp_training_program (weekly_activity_report_template_id)');
        $this->addSql('CREATE INDEX IDX_AF5EDD7B7791D10 ON itp_training_program (activity_summary_report_template_id)');
        $this->addSql('ALTER TABLE itp_training_program_audit ADD student_survey_id INT DEFAULT NULL, ADD company_survey_id INT DEFAULT NULL, ADD educational_tutor_survey_id INT DEFAULT NULL, ADD attendance_report_template_id INT DEFAULT NULL, ADD final_report_template_id INT DEFAULT NULL, ADD weekly_activity_report_template_id INT DEFAULT NULL, ADD activity_summary_report_template_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_training_program DROP FOREIGN KEY FK_AF5EDD7BD490911D');
        $this->addSql('ALTER TABLE itp_training_program DROP FOREIGN KEY FK_AF5EDD7B80E5DA6D');
        $this->addSql('ALTER TABLE itp_training_program DROP FOREIGN KEY FK_AF5EDD7B68F6798B');
        $this->addSql('ALTER TABLE itp_training_program DROP FOREIGN KEY FK_AF5EDD7B13E6472B');
        $this->addSql('ALTER TABLE itp_training_program DROP FOREIGN KEY FK_AF5EDD7B10BDE131');
        $this->addSql('ALTER TABLE itp_training_program DROP FOREIGN KEY FK_AF5EDD7B3BBF8EEC');
        $this->addSql('ALTER TABLE itp_training_program DROP FOREIGN KEY FK_AF5EDD7B7791D10');
        $this->addSql('DROP INDEX IDX_AF5EDD7BD490911D ON itp_training_program');
        $this->addSql('DROP INDEX IDX_AF5EDD7B80E5DA6D ON itp_training_program');
        $this->addSql('DROP INDEX IDX_AF5EDD7B68F6798B ON itp_training_program');
        $this->addSql('DROP INDEX IDX_AF5EDD7B13E6472B ON itp_training_program');
        $this->addSql('DROP INDEX IDX_AF5EDD7B10BDE131 ON itp_training_program');
        $this->addSql('DROP INDEX IDX_AF5EDD7B3BBF8EEC ON itp_training_program');
        $this->addSql('DROP INDEX IDX_AF5EDD7B7791D10 ON itp_training_program');
        $this->addSql('ALTER TABLE itp_training_program DROP student_survey_id, DROP company_survey_id, DROP educational_tutor_survey_id, DROP attendance_report_template_id, DROP final_report_template_id, DROP weekly_activity_report_template_id, DROP activity_summary_report_template_id');
        $this->addSql('ALTER TABLE itp_training_program_audit DROP student_survey_id, DROP company_survey_id, DROP educational_tutor_survey_id, DROP attendance_report_template_id, DROP final_report_template_id, DROP weekly_activity_report_template_id, DROP activity_summary_report_template_id');
    }
}
