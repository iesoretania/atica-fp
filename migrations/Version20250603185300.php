<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250603185300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE itp_educational_tutor_answered_survey (id INT AUTO_INCREMENT NOT NULL, training_program_id INT NOT NULL, teacher_id INT NOT NULL, answered_survey_id INT NOT NULL, INDEX IDX_D8F214A68406BD6C (training_program_id), INDEX IDX_D8F214A641807E1D (teacher_id), INDEX IDX_D8F214A6A97283E6 (answered_survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_educational_tutor_answered_survey_audit (id INT NOT NULL, rev INT NOT NULL, training_program_id INT DEFAULT NULL, teacher_id INT DEFAULT NULL, answered_survey_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_38efae102c3fd89ad405521f3b727132_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_student_answered_survey (id INT AUTO_INCREMENT NOT NULL, student_program_workcenter_id INT NOT NULL, student_enrollment_id INT NOT NULL, answered_survey_id INT NOT NULL, INDEX IDX_C093319BF0C2682D (student_program_workcenter_id), INDEX IDX_C093319BDAE14AC5 (student_enrollment_id), INDEX IDX_C093319BA97283E6 (answered_survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_student_answered_survey_audit (id INT NOT NULL, rev INT NOT NULL, student_program_workcenter_id INT DEFAULT NULL, student_enrollment_id INT DEFAULT NULL, answered_survey_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_1a93d5d96185253714565e301f10fe27_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_work_tutor_answered_survey (id INT AUTO_INCREMENT NOT NULL, training_program_id INT NOT NULL, academic_year_id INT NOT NULL, work_tutor_id INT NOT NULL, answered_survey_id INT NOT NULL, INDEX IDX_5B7F9F038406BD6C (training_program_id), INDEX IDX_5B7F9F03C54F3401 (academic_year_id), INDEX IDX_5B7F9F03F53AEEAD (work_tutor_id), INDEX IDX_5B7F9F03A97283E6 (answered_survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_work_tutor_answered_survey_audit (id INT NOT NULL, rev INT NOT NULL, training_program_id INT DEFAULT NULL, academic_year_id INT DEFAULT NULL, work_tutor_id INT DEFAULT NULL, answered_survey_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_10863b6a7ed3dcadea8594902a57e17a_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE itp_educational_tutor_answered_survey ADD CONSTRAINT FK_D8F214A68406BD6C FOREIGN KEY (training_program_id) REFERENCES itp_training_program (id)');
        $this->addSql('ALTER TABLE itp_educational_tutor_answered_survey ADD CONSTRAINT FK_D8F214A641807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE itp_educational_tutor_answered_survey ADD CONSTRAINT FK_D8F214A6A97283E6 FOREIGN KEY (answered_survey_id) REFERENCES answered_survey (id)');
        $this->addSql('ALTER TABLE itp_educational_tutor_answered_survey_audit ADD CONSTRAINT rev_38efae102c3fd89ad405521f3b727132_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_student_answered_survey ADD CONSTRAINT FK_C093319BF0C2682D FOREIGN KEY (student_program_workcenter_id) REFERENCES itp_student_program_workcenter (id)');
        $this->addSql('ALTER TABLE itp_student_answered_survey ADD CONSTRAINT FK_C093319BDAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id)');
        $this->addSql('ALTER TABLE itp_student_answered_survey ADD CONSTRAINT FK_C093319BA97283E6 FOREIGN KEY (answered_survey_id) REFERENCES answered_survey (id)');
        $this->addSql('ALTER TABLE itp_student_answered_survey_audit ADD CONSTRAINT rev_1a93d5d96185253714565e301f10fe27_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_work_tutor_answered_survey ADD CONSTRAINT FK_5B7F9F038406BD6C FOREIGN KEY (training_program_id) REFERENCES itp_training_program (id)');
        $this->addSql('ALTER TABLE itp_work_tutor_answered_survey ADD CONSTRAINT FK_5B7F9F03C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE itp_work_tutor_answered_survey ADD CONSTRAINT FK_5B7F9F03F53AEEAD FOREIGN KEY (work_tutor_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE itp_work_tutor_answered_survey ADD CONSTRAINT FK_5B7F9F03A97283E6 FOREIGN KEY (answered_survey_id) REFERENCES answered_survey (id)');
        $this->addSql('ALTER TABLE itp_work_tutor_answered_survey_audit ADD CONSTRAINT rev_10863b6a7ed3dcadea8594902a57e17a_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_educational_tutor_answered_survey DROP FOREIGN KEY FK_D8F214A68406BD6C');
        $this->addSql('ALTER TABLE itp_educational_tutor_answered_survey DROP FOREIGN KEY FK_D8F214A641807E1D');
        $this->addSql('ALTER TABLE itp_educational_tutor_answered_survey DROP FOREIGN KEY FK_D8F214A6A97283E6');
        $this->addSql('ALTER TABLE itp_educational_tutor_answered_survey_audit DROP FOREIGN KEY rev_38efae102c3fd89ad405521f3b727132_fk');
        $this->addSql('ALTER TABLE itp_student_answered_survey DROP FOREIGN KEY FK_C093319BF0C2682D');
        $this->addSql('ALTER TABLE itp_student_answered_survey DROP FOREIGN KEY FK_C093319BDAE14AC5');
        $this->addSql('ALTER TABLE itp_student_answered_survey DROP FOREIGN KEY FK_C093319BA97283E6');
        $this->addSql('ALTER TABLE itp_student_answered_survey_audit DROP FOREIGN KEY rev_1a93d5d96185253714565e301f10fe27_fk');
        $this->addSql('ALTER TABLE itp_work_tutor_answered_survey DROP FOREIGN KEY FK_5B7F9F038406BD6C');
        $this->addSql('ALTER TABLE itp_work_tutor_answered_survey DROP FOREIGN KEY FK_5B7F9F03C54F3401');
        $this->addSql('ALTER TABLE itp_work_tutor_answered_survey DROP FOREIGN KEY FK_5B7F9F03F53AEEAD');
        $this->addSql('ALTER TABLE itp_work_tutor_answered_survey DROP FOREIGN KEY FK_5B7F9F03A97283E6');
        $this->addSql('ALTER TABLE itp_work_tutor_answered_survey_audit DROP FOREIGN KEY rev_10863b6a7ed3dcadea8594902a57e17a_fk');
        $this->addSql('DROP TABLE itp_educational_tutor_answered_survey');
        $this->addSql('DROP TABLE itp_educational_tutor_answered_survey_audit');
        $this->addSql('DROP TABLE itp_student_answered_survey');
        $this->addSql('DROP TABLE itp_student_answered_survey_audit');
        $this->addSql('DROP TABLE itp_work_tutor_answered_survey');
        $this->addSql('DROP TABLE itp_work_tutor_answered_survey_audit');
    }
}
