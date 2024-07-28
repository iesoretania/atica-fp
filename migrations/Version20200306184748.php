<?php

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200306184748 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE wpt_agreement_enrollment (id INT AUTO_INCREMENT NOT NULL, agreement_id INT NOT NULL, student_enrollment_id INT NOT NULL, student_survey_id INT DEFAULT NULL, INDEX IDX_A24B4F7824890B2B (agreement_id), INDEX IDX_A24B4F78DAE14AC5 (student_enrollment_id), INDEX IDX_A24B4F78D490911D (student_survey_id), UNIQUE INDEX UNIQ_A24B4F7824890B2BDAE14AC5 (agreement_id, student_enrollment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_tracked_work_day (id INT AUTO_INCREMENT NOT NULL, work_day_id INT NOT NULL, student_enrollment_id INT NOT NULL, hours INT NOT NULL, date DATE NOT NULL, notes LONGTEXT DEFAULT NULL, other_activities LONGTEXT DEFAULT NULL, locked TINYINT(1) NOT NULL, absence INT NOT NULL, start_time1 VARCHAR(5) DEFAULT NULL, end_time1 VARCHAR(5) DEFAULT NULL, start_time2 VARCHAR(5) DEFAULT NULL, end_time2 VARCHAR(5) DEFAULT NULL, INDEX IDX_E7E67EDAA23B8704 (work_day_id), INDEX IDX_E7E67EDADAE14AC5 (student_enrollment_id), UNIQUE INDEX UNIQ_E7E67EDADAE14AC5A23B8704 (student_enrollment_id, work_day_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wpt_agreement_enrollment ADD CONSTRAINT FK_A24B4F7824890B2B FOREIGN KEY (agreement_id) REFERENCES wpt_agreement (id)');
        $this->addSql('ALTER TABLE wpt_agreement_enrollment ADD CONSTRAINT FK_A24B4F78DAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id)');
        $this->addSql('ALTER TABLE wpt_agreement_enrollment ADD CONSTRAINT FK_A24B4F78D490911D FOREIGN KEY (student_survey_id) REFERENCES answered_survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE wpt_tracked_work_day ADD CONSTRAINT FK_E7E67EDAA23B8704 FOREIGN KEY (work_day_id) REFERENCES wpt_work_day (id)');
        $this->addSql('ALTER TABLE wpt_tracked_work_day ADD CONSTRAINT FK_E7E67EDADAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id)');
        $this->addSql('DROP TABLE wpt_shift_student_enrollment');
        $this->addSql('ALTER TABLE wpt_activity_tracking DROP FOREIGN KEY FK_FD826545AB01D695');
        $this->addSql('DROP INDEX IDX_FD826545AB01D695 ON wpt_activity_tracking');
        $this->addSql('ALTER TABLE wpt_activity_tracking DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wpt_activity_tracking CHANGE workday_id tracked_work_day_id INT NOT NULL');
        $this->addSql('ALTER TABLE wpt_activity_tracking ADD CONSTRAINT FK_FD826545BE757E4C FOREIGN KEY (tracked_work_day_id) REFERENCES wpt_tracked_work_day (id)');
        $this->addSql('CREATE INDEX IDX_FD826545BE757E4C ON wpt_activity_tracking (tracked_work_day_id)');
        $this->addSql('ALTER TABLE wpt_activity_tracking ADD PRIMARY KEY (tracked_work_day_id, activity_id)');
        $this->addSql('ALTER TABLE wpt_work_day DROP notes, DROP other_activities, DROP locked, DROP absence');
        $this->addSql('ALTER TABLE wpt_work_day_audit DROP notes, DROP other_activities, DROP locked, DROP absence');
        $this->addSql('ALTER TABLE wpt_agreement DROP FOREIGN KEY FK_22310F94D490911D');
        $this->addSql('ALTER TABLE wpt_agreement DROP FOREIGN KEY FK_22310F94DAE14AC5');
        $this->addSql('DROP INDEX UNIQ_22310F94BB70BC0EDAE14AC5A2473C4B ON wpt_agreement');
        $this->addSql('DROP INDEX IDX_22310F94DAE14AC5 ON wpt_agreement');
        $this->addSql('DROP INDEX IDX_22310F94D490911D ON wpt_agreement');
        $this->addSql('ALTER TABLE wpt_agreement ADD name VARCHAR(255) DEFAULT NULL, DROP student_survey_id, DROP student_enrollment_id');
        $this->addSql('ALTER TABLE wpt_agreement_audit ADD name VARCHAR(255) DEFAULT NULL, DROP student_enrollment_id, DROP student_survey_id');
        $this->addSql('ALTER TABLE wpt_report DROP FOREIGN KEY FK_8FD6E55124890B2B');
        $this->addSql('ALTER TABLE wpt_report DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wpt_report CHANGE agreement_id agreement_enrollment_id INT NOT NULL');
        $this->addSql('ALTER TABLE wpt_report ADD CONSTRAINT FK_8FD6E551A32BEB28 FOREIGN KEY (agreement_enrollment_id) REFERENCES wpt_agreement_enrollment (id)');
        $this->addSql('ALTER TABLE wpt_report ADD PRIMARY KEY (agreement_enrollment_id)');
        $this->addSql('ALTER TABLE wpt_report_audit DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wpt_report_audit CHANGE agreement_id agreement_enrollment_id INT NOT NULL');
        $this->addSql('ALTER TABLE wpt_report_audit ADD PRIMARY KEY (agreement_enrollment_id, rev)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wpt_report DROP FOREIGN KEY FK_8FD6E551A32BEB28');
        $this->addSql('ALTER TABLE wpt_activity_tracking DROP FOREIGN KEY FK_FD826545BE757E4C');
        $this->addSql('CREATE TABLE wpt_shift_student_enrollment (shift_id INT NOT NULL, student_enrollment_id INT NOT NULL, INDEX IDX_2D53063BB70BC0E (shift_id), INDEX IDX_2D53063DAE14AC5 (student_enrollment_id), PRIMARY KEY(shift_id, student_enrollment_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wpt_shift_student_enrollment ADD CONSTRAINT FK_2D53063BB70BC0E FOREIGN KEY (shift_id) REFERENCES wpt_shift (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wpt_shift_student_enrollment ADD CONSTRAINT FK_2D53063DAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE wpt_agreement_enrollment');
        $this->addSql('DROP TABLE wpt_tracked_work_day');
        $this->addSql('DROP INDEX IDX_FD826545BE757E4C ON wpt_activity_tracking');
        $this->addSql('ALTER TABLE wpt_activity_tracking DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wpt_activity_tracking CHANGE tracked_work_day_id workday_id INT NOT NULL');
        $this->addSql('ALTER TABLE wpt_activity_tracking ADD CONSTRAINT FK_FD826545AB01D695 FOREIGN KEY (workday_id) REFERENCES wpt_work_day (id)');
        $this->addSql('CREATE INDEX IDX_FD826545AB01D695 ON wpt_activity_tracking (workday_id)');
        $this->addSql('ALTER TABLE wpt_activity_tracking ADD PRIMARY KEY (workday_id, activity_id)');
        $this->addSql('ALTER TABLE wpt_agreement ADD student_survey_id INT DEFAULT NULL, ADD student_enrollment_id INT NOT NULL, DROP name');
        $this->addSql('ALTER TABLE wpt_agreement ADD CONSTRAINT FK_22310F94D490911D FOREIGN KEY (student_survey_id) REFERENCES answered_survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE wpt_agreement ADD CONSTRAINT FK_22310F94DAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_22310F94BB70BC0EDAE14AC5A2473C4B ON wpt_agreement (shift_id, student_enrollment_id, workcenter_id)');
        $this->addSql('CREATE INDEX IDX_22310F94DAE14AC5 ON wpt_agreement (student_enrollment_id)');
        $this->addSql('CREATE INDEX IDX_22310F94D490911D ON wpt_agreement (student_survey_id)');
        $this->addSql('ALTER TABLE wpt_agreement_audit ADD student_enrollment_id INT DEFAULT NULL, ADD student_survey_id INT DEFAULT NULL, DROP name');
        $this->addSql('ALTER TABLE wpt_report DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wpt_report CHANGE agreement_enrollment_id agreement_id INT NOT NULL');
        $this->addSql('ALTER TABLE wpt_report ADD CONSTRAINT FK_8FD6E55124890B2B FOREIGN KEY (agreement_id) REFERENCES wpt_agreement (id)');
        $this->addSql('ALTER TABLE wpt_report ADD PRIMARY KEY (agreement_id)');
        $this->addSql('ALTER TABLE wpt_report_audit DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wpt_report_audit CHANGE agreement_enrollment_id agreement_id INT NOT NULL');
        $this->addSql('ALTER TABLE wpt_report_audit ADD PRIMARY KEY (agreement_id, rev)');
        $this->addSql('ALTER TABLE wpt_work_day ADD notes LONGTEXT DEFAULT NULL COLLATE utf8mb4_spanish_ci, ADD other_activities LONGTEXT DEFAULT NULL COLLATE utf8mb4_spanish_ci, ADD locked TINYINT(1) NOT NULL, ADD absence INT NOT NULL');
        $this->addSql('ALTER TABLE wpt_work_day_audit ADD notes LONGTEXT DEFAULT NULL COLLATE utf8mb4_spanish_ci, ADD other_activities LONGTEXT DEFAULT NULL COLLATE utf8mb4_spanish_ci, ADD locked TINYINT(1) DEFAULT NULL, ADD absence INT DEFAULT NULL');
    }
}
