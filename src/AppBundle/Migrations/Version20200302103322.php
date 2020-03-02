<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200302103322 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE edu_criterion (id INT AUTO_INCREMENT NOT NULL, learning_outcome_id INT NOT NULL, code VARCHAR(255) DEFAULT NULL, name LONGTEXT NOT NULL, description LONGTEXT DEFAULT NULL, order_nr INT DEFAULT NULL, INDEX IDX_6A1D6D1335C2B2D5 (learning_outcome_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_activity_tracking (workday_id INT NOT NULL, activity_id INT NOT NULL, notes LONGTEXT DEFAULT NULL, hours INT NOT NULL, INDEX IDX_FD826545AB01D695 (workday_id), INDEX IDX_FD82654581C06096 (activity_id), PRIMARY KEY(workday_id, activity_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_shift (id INT AUTO_INCREMENT NOT NULL, subject_id INT NOT NULL, student_survey_id INT DEFAULT NULL, company_survey_id INT DEFAULT NULL, educational_tutor_survey_id INT DEFAULT NULL, attendance_report_template_id INT DEFAULT NULL, weekly_activity_report_template_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, hours INT NOT NULL, type VARCHAR(255) NOT NULL, quarter INT NOT NULL, INDEX IDX_584DA96023EDC87 (subject_id), INDEX IDX_584DA960D490911D (student_survey_id), INDEX IDX_584DA96080E5DA6D (company_survey_id), INDEX IDX_584DA96068F6798B (educational_tutor_survey_id), INDEX IDX_584DA96013E6472B (attendance_report_template_id), INDEX IDX_584DA9603BBF8EEC (weekly_activity_report_template_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_shift_student_enrollment (shift_id INT NOT NULL, student_enrollment_id INT NOT NULL, INDEX IDX_2D53063BB70BC0E (shift_id), INDEX IDX_2D53063DAE14AC5 (student_enrollment_id), PRIMARY KEY(shift_id, student_enrollment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_activity (id INT AUTO_INCREMENT NOT NULL, shift_id INT NOT NULL, code VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, INDEX IDX_4E3A8191BB70BC0E (shift_id), UNIQUE INDEX UNIQ_4E3A8191BB70BC0E77153098 (shift_id, code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_activity_criterion (activity_id INT NOT NULL, criterion_id INT NOT NULL, INDEX IDX_6E60352681C06096 (activity_id), INDEX IDX_6E60352697766307 (criterion_id), PRIMARY KEY(activity_id, criterion_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_work_day (id INT AUTO_INCREMENT NOT NULL, agreement_id INT NOT NULL, hours INT NOT NULL, date DATE NOT NULL, notes LONGTEXT DEFAULT NULL, other_activities LONGTEXT DEFAULT NULL, locked TINYINT(1) NOT NULL, absence INT NOT NULL, start_time1 VARCHAR(5) DEFAULT NULL, end_time1 VARCHAR(5) DEFAULT NULL, start_time2 VARCHAR(5) DEFAULT NULL, end_time2 VARCHAR(5) DEFAULT NULL, INDEX IDX_7D80F6C724890B2B (agreement_id), UNIQUE INDEX UNIQ_7D80F6C724890B2BAA9E377A (agreement_id, date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_agreement (id INT AUTO_INCREMENT NOT NULL, shift_id INT NOT NULL, workcenter_id INT NOT NULL, student_enrollment_id INT NOT NULL, work_tutor_id INT NOT NULL, educational_tutor_id INT NOT NULL, student_survey_id INT DEFAULT NULL, company_survey_id INT DEFAULT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, default_start_time1 VARCHAR(5) DEFAULT NULL, default_end_time1 VARCHAR(5) DEFAULT NULL, default_start_time2 VARCHAR(5) DEFAULT NULL, default_end_time2 VARCHAR(5) DEFAULT NULL, INDEX IDX_22310F94BB70BC0E (shift_id), INDEX IDX_22310F94A2473C4B (workcenter_id), INDEX IDX_22310F94DAE14AC5 (student_enrollment_id), INDEX IDX_22310F94F53AEEAD (work_tutor_id), INDEX IDX_22310F94E7F72E80 (educational_tutor_id), INDEX IDX_22310F94D490911D (student_survey_id), INDEX IDX_22310F9480E5DA6D (company_survey_id), UNIQUE INDEX UNIQ_22310F94BB70BC0EDAE14AC5A2473C4B (shift_id, student_enrollment_id, workcenter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_agreement_activity (agreement_id INT NOT NULL, activity_id INT NOT NULL, INDEX IDX_889D477F24890B2B (agreement_id), INDEX IDX_889D477F81C06096 (activity_id), PRIMARY KEY(agreement_id, activity_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE edu_criterion ADD CONSTRAINT FK_6A1D6D1335C2B2D5 FOREIGN KEY (learning_outcome_id) REFERENCES edu_learning_outcome (id)');
        $this->addSql('ALTER TABLE wpt_activity_tracking ADD CONSTRAINT FK_FD826545AB01D695 FOREIGN KEY (workday_id) REFERENCES wpt_work_day (id)');
        $this->addSql('ALTER TABLE wpt_activity_tracking ADD CONSTRAINT FK_FD82654581C06096 FOREIGN KEY (activity_id) REFERENCES wpt_activity (id)');
        $this->addSql('ALTER TABLE wpt_shift ADD CONSTRAINT FK_584DA96023EDC87 FOREIGN KEY (subject_id) REFERENCES edu_subject (id)');
        $this->addSql('ALTER TABLE wpt_shift ADD CONSTRAINT FK_584DA960D490911D FOREIGN KEY (student_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE wpt_shift ADD CONSTRAINT FK_584DA96080E5DA6D FOREIGN KEY (company_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE wpt_shift ADD CONSTRAINT FK_584DA96068F6798B FOREIGN KEY (educational_tutor_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE wpt_shift ADD CONSTRAINT FK_584DA96013E6472B FOREIGN KEY (attendance_report_template_id) REFERENCES edu_report_template (id)');
        $this->addSql('ALTER TABLE wpt_shift ADD CONSTRAINT FK_584DA9603BBF8EEC FOREIGN KEY (weekly_activity_report_template_id) REFERENCES edu_report_template (id)');
        $this->addSql('ALTER TABLE wpt_shift_student_enrollment ADD CONSTRAINT FK_2D53063BB70BC0E FOREIGN KEY (shift_id) REFERENCES wpt_shift (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wpt_shift_student_enrollment ADD CONSTRAINT FK_2D53063DAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wpt_activity ADD CONSTRAINT FK_4E3A8191BB70BC0E FOREIGN KEY (shift_id) REFERENCES wpt_shift (id)');
        $this->addSql('ALTER TABLE wpt_activity_criterion ADD CONSTRAINT FK_6E60352681C06096 FOREIGN KEY (activity_id) REFERENCES wpt_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wpt_activity_criterion ADD CONSTRAINT FK_6E60352697766307 FOREIGN KEY (criterion_id) REFERENCES edu_criterion (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wpt_work_day ADD CONSTRAINT FK_7D80F6C724890B2B FOREIGN KEY (agreement_id) REFERENCES wpt_agreement (id)');
        $this->addSql('ALTER TABLE wpt_agreement ADD CONSTRAINT FK_22310F94BB70BC0E FOREIGN KEY (shift_id) REFERENCES wpt_shift (id)');
        $this->addSql('ALTER TABLE wpt_agreement ADD CONSTRAINT FK_22310F94A2473C4B FOREIGN KEY (workcenter_id) REFERENCES workcenter (id)');
        $this->addSql('ALTER TABLE wpt_agreement ADD CONSTRAINT FK_22310F94DAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id)');
        $this->addSql('ALTER TABLE wpt_agreement ADD CONSTRAINT FK_22310F94F53AEEAD FOREIGN KEY (work_tutor_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE wpt_agreement ADD CONSTRAINT FK_22310F94E7F72E80 FOREIGN KEY (educational_tutor_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE wpt_agreement ADD CONSTRAINT FK_22310F94D490911D FOREIGN KEY (student_survey_id) REFERENCES answered_survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE wpt_agreement ADD CONSTRAINT FK_22310F9480E5DA6D FOREIGN KEY (company_survey_id) REFERENCES answered_survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE wpt_agreement_activity ADD CONSTRAINT FK_889D477F24890B2B FOREIGN KEY (agreement_id) REFERENCES wpt_agreement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wpt_agreement_activity ADD CONSTRAINT FK_889D477F81C06096 FOREIGN KEY (activity_id) REFERENCES wpt_activity (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX UNIQ_2B23AFE9DAE14AC5A2473C4B ON wlt_agreement');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2B23AFE9166D1F9CDAE14AC5A2473C4B ON wlt_agreement (project_id, student_enrollment_id, workcenter_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wpt_activity_criterion DROP FOREIGN KEY FK_6E60352697766307');
        $this->addSql('ALTER TABLE wpt_shift_student_enrollment DROP FOREIGN KEY FK_2D53063BB70BC0E');
        $this->addSql('ALTER TABLE wpt_activity DROP FOREIGN KEY FK_4E3A8191BB70BC0E');
        $this->addSql('ALTER TABLE wpt_agreement DROP FOREIGN KEY FK_22310F94BB70BC0E');
        $this->addSql('ALTER TABLE wpt_activity_tracking DROP FOREIGN KEY FK_FD82654581C06096');
        $this->addSql('ALTER TABLE wpt_activity_criterion DROP FOREIGN KEY FK_6E60352681C06096');
        $this->addSql('ALTER TABLE wpt_agreement_activity DROP FOREIGN KEY FK_889D477F81C06096');
        $this->addSql('ALTER TABLE wpt_activity_tracking DROP FOREIGN KEY FK_FD826545AB01D695');
        $this->addSql('ALTER TABLE wpt_work_day DROP FOREIGN KEY FK_7D80F6C724890B2B');
        $this->addSql('ALTER TABLE wpt_agreement_activity DROP FOREIGN KEY FK_889D477F24890B2B');
        $this->addSql('DROP TABLE edu_criterion');
        $this->addSql('DROP TABLE wpt_activity_tracking');
        $this->addSql('DROP TABLE wpt_shift');
        $this->addSql('DROP TABLE wpt_shift_student_enrollment');
        $this->addSql('DROP TABLE wpt_activity');
        $this->addSql('DROP TABLE wpt_activity_criterion');
        $this->addSql('DROP TABLE wpt_work_day');
        $this->addSql('DROP TABLE wpt_agreement');
        $this->addSql('DROP TABLE wpt_agreement_activity');
        $this->addSql('DROP INDEX UNIQ_2B23AFE9166D1F9CDAE14AC5A2473C4B ON wlt_agreement');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2B23AFE9DAE14AC5A2473C4B ON wlt_agreement (student_enrollment_id, workcenter_id)');
    }
}
