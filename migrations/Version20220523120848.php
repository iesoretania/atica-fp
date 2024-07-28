<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220523120848 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE wlt_educational_tutor_answered_survey_audit (id INT NOT NULL, rev INT NOT NULL, project_id INT DEFAULT NULL, teacher_id INT DEFAULT NULL, answered_survey_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_68c9c40ff36ffd2b1378aa4b2746dcac_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_student_answered_survey_audit (id INT NOT NULL, rev INT NOT NULL, project_id INT DEFAULT NULL, student_enrollment_id INT DEFAULT NULL, answered_survey_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_7233771bba2039be6047b538a296dbb3_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_work_tutor_answered_survey_audit (id INT NOT NULL, rev INT NOT NULL, project_id INT DEFAULT NULL, academic_year_id INT DEFAULT NULL, work_tutor_id INT DEFAULT NULL, answered_survey_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_583e3ede99c93193ba43436008b164e7_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_educational_tutor_answered_survey (id INT AUTO_INCREMENT NOT NULL, shift_id INT NOT NULL, teacher_id INT NOT NULL, answered_survey_id INT NOT NULL, INDEX IDX_62609090BB70BC0E (shift_id), INDEX IDX_6260909041807E1D (teacher_id), INDEX IDX_62609090A97283E6 (answered_survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_educational_tutor_answered_survey_audit (id INT NOT NULL, rev INT NOT NULL, shift_id INT DEFAULT NULL, teacher_id INT DEFAULT NULL, answered_survey_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_d3a1a7f9086d9bd8a93c67e81b8519eb_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_student_answered_survey (id INT AUTO_INCREMENT NOT NULL, shift_id INT NOT NULL, student_enrollment_id INT NOT NULL, answered_survey_id INT NOT NULL, INDEX IDX_3115CDA6BB70BC0E (shift_id), INDEX IDX_3115CDA6DAE14AC5 (student_enrollment_id), INDEX IDX_3115CDA6A97283E6 (answered_survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_student_answered_survey_audit (id INT NOT NULL, rev INT NOT NULL, shift_id INT DEFAULT NULL, student_enrollment_id INT DEFAULT NULL, answered_survey_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_18ec14100e728ec30e97808504c4bcae_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_work_tutor_answered_survey (id INT AUTO_INCREMENT NOT NULL, shift_id INT NOT NULL, work_tutor_id INT NOT NULL, answered_survey_id INT NOT NULL, INDEX IDX_CB1E6195BB70BC0E (shift_id), INDEX IDX_CB1E6195F53AEEAD (work_tutor_id), INDEX IDX_CB1E6195A97283E6 (answered_survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_work_tutor_answered_survey_audit (id INT NOT NULL, rev INT NOT NULL, shift_id INT DEFAULT NULL, work_tutor_id INT DEFAULT NULL, answered_survey_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_9b088cb8b5a15bf14b41b058e4fb67c5_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wpt_educational_tutor_answered_survey ADD CONSTRAINT FK_62609090BB70BC0E FOREIGN KEY (shift_id) REFERENCES wpt_shift (id)');
        $this->addSql('ALTER TABLE wpt_educational_tutor_answered_survey ADD CONSTRAINT FK_6260909041807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE wpt_educational_tutor_answered_survey ADD CONSTRAINT FK_62609090A97283E6 FOREIGN KEY (answered_survey_id) REFERENCES answered_survey (id)');
        $this->addSql('ALTER TABLE wpt_student_answered_survey ADD CONSTRAINT FK_3115CDA6BB70BC0E FOREIGN KEY (shift_id) REFERENCES wpt_shift (id)');
        $this->addSql('ALTER TABLE wpt_student_answered_survey ADD CONSTRAINT FK_3115CDA6DAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id)');
        $this->addSql('ALTER TABLE wpt_student_answered_survey ADD CONSTRAINT FK_3115CDA6A97283E6 FOREIGN KEY (answered_survey_id) REFERENCES answered_survey (id)');
        $this->addSql('ALTER TABLE wpt_work_tutor_answered_survey ADD CONSTRAINT FK_CB1E6195BB70BC0E FOREIGN KEY (shift_id) REFERENCES wpt_shift (id)');
        $this->addSql('ALTER TABLE wpt_work_tutor_answered_survey ADD CONSTRAINT FK_CB1E6195F53AEEAD FOREIGN KEY (work_tutor_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE wpt_work_tutor_answered_survey ADD CONSTRAINT FK_CB1E6195A97283E6 FOREIGN KEY (answered_survey_id) REFERENCES answered_survey (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE wlt_educational_tutor_answered_survey_audit');
        $this->addSql('DROP TABLE wlt_student_answered_survey_audit');
        $this->addSql('DROP TABLE wlt_work_tutor_answered_survey_audit');
        $this->addSql('DROP TABLE wpt_educational_tutor_answered_survey');
        $this->addSql('DROP TABLE wpt_educational_tutor_answered_survey_audit');
        $this->addSql('DROP TABLE wpt_student_answered_survey');
        $this->addSql('DROP TABLE wpt_student_answered_survey_audit');
        $this->addSql('DROP TABLE wpt_work_tutor_answered_survey');
        $this->addSql('DROP TABLE wpt_work_tutor_answered_survey_audit');
    }
}
