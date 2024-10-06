<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241007062837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP FOREIGN KEY FK_BD86F078FE19A1A8');
        $this->addSql('CREATE TABLE edu_performance_scale (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, description VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, INDEX IDX_97FBF6F932C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_performance_scale_audit (id INT NOT NULL, rev INT NOT NULL, organization_id INT DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_872a21aa478051cdedd389213a503b28_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_performance_scale_value (id INT AUTO_INCREMENT NOT NULL, performance_scale_id INT NOT NULL, description VARCHAR(255) NOT NULL, numeric_grade INT NOT NULL, notes LONGTEXT DEFAULT NULL, INDEX IDX_CB9D66577C2C26C0 (performance_scale_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_performance_scale_value_audit (id INT NOT NULL, rev INT NOT NULL, performance_scale_id INT DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, numeric_grade INT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_251f0c8c6a4a618acff9d2ea364b6c7d_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_activity (id INT AUTO_INCREMENT NOT NULL, program_grade_id INT NOT NULL, code VARCHAR(255) NOT NULL, name LONGTEXT NOT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_12216C8B7F2C5D9D (program_grade_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_activity_criterion (activity_id INT NOT NULL, criterion_id INT NOT NULL, INDEX IDX_5031EC0581C06096 (activity_id), INDEX IDX_5031EC0597766307 (criterion_id), PRIMARY KEY(activity_id, criterion_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_activity_audit (id INT NOT NULL, rev INT NOT NULL, program_grade_id INT DEFAULT NULL, code VARCHAR(255) DEFAULT NULL, name LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_ec81cce9357b10e123ad7a8a61086606_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_activity_criterion_audit (activity_id INT NOT NULL, criterion_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_84340deadd947925141c5ccb0e2175c4_idx (rev), PRIMARY KEY(activity_id, criterion_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_company_program (id INT AUTO_INCREMENT NOT NULL, program_grade_id INT NOT NULL, company_id INT NOT NULL, agreement_number VARCHAR(255) DEFAULT NULL, monitoring_instruments LONGTEXT DEFAULT NULL, INDEX IDX_EA6C82597F2C5D9D (program_grade_id), INDEX IDX_EA6C8259979B1AD6 (company_id), UNIQUE INDEX company_program_unique (program_grade_id, company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_company_program_activity (company_program_id INT NOT NULL, activity_id INT NOT NULL, INDEX IDX_B69227C58336EE9C (company_program_id), INDEX IDX_B69227C581C06096 (activity_id), PRIMARY KEY(company_program_id, activity_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_company_program_audit (id INT NOT NULL, rev INT NOT NULL, program_grade_id INT DEFAULT NULL, company_id INT DEFAULT NULL, agreement_number VARCHAR(255) DEFAULT NULL, monitoring_instruments LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_2fa1ce202ffe5764db7e79671701410e_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_company_program_activity_audit (company_program_id INT NOT NULL, activity_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_16dda1ed93f1412f09bd9e0332001f79_idx (rev), PRIMARY KEY(company_program_id, activity_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_program_grade (id INT AUTO_INCREMENT NOT NULL, training_program_id INT NOT NULL, grade_id INT NOT NULL, target_hours INT DEFAULT NULL, INDEX IDX_6F6FE3778406BD6C (training_program_id), INDEX IDX_6F6FE377FE19A1A8 (grade_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE program_grade_subject (program_grade_id INT NOT NULL, subject_id INT NOT NULL, INDEX IDX_A4CABC007F2C5D9D (program_grade_id), INDEX IDX_A4CABC0023EDC87 (subject_id), PRIMARY KEY(program_grade_id, subject_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_program_grade_audit (id INT NOT NULL, rev INT NOT NULL, training_program_id INT DEFAULT NULL, grade_id INT DEFAULT NULL, target_hours INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_77fa13b589b0147f9bba81f01535097c_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE program_grade_subject_audit (program_grade_id INT NOT NULL, subject_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_041a01c6d62e76938a2845890cdd3c63_idx (rev), PRIMARY KEY(program_grade_id, subject_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_program_grade_learning_outcome (id INT AUTO_INCREMENT NOT NULL, program_grade_id INT NOT NULL, learning_outcome_id INT NOT NULL, shared TINYINT(1) NOT NULL, INDEX IDX_99777B9D7F2C5D9D (program_grade_id), INDEX IDX_99777B9D35C2B2D5 (learning_outcome_id), UNIQUE INDEX itp_program_grade_learning_outcome_unique (program_grade_id, learning_outcome_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_program_grade_learning_outcome_audit (id INT NOT NULL, rev INT NOT NULL, program_grade_id INT DEFAULT NULL, learning_outcome_id INT DEFAULT NULL, shared TINYINT(1) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_e8feacf1d336f619b32e00ebe3fef4f3_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_program_group (id INT AUTO_INCREMENT NOT NULL, program_grade_id INT NOT NULL, group_id INT NOT NULL, locked TINYINT(1) NOT NULL, notes LONGTEXT DEFAULT NULL, modality INT NOT NULL, target_hours INT DEFAULT NULL, INDEX IDX_5BF509867F2C5D9D (program_grade_id), INDEX IDX_5BF50986FE54D947 (group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_program_group_manager (program_group_id INT NOT NULL, teacher_id INT NOT NULL, INDEX IDX_843C7B3A7F612572 (program_group_id), INDEX IDX_843C7B3A41807E1D (teacher_id), PRIMARY KEY(program_group_id, teacher_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_program_group_audit (id INT NOT NULL, rev INT NOT NULL, program_grade_id INT DEFAULT NULL, group_id INT DEFAULT NULL, locked TINYINT(1) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, modality INT DEFAULT NULL, target_hours INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_1bbc384c45dccae2d1b5f0fa90bcce44_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_program_group_manager_audit (program_group_id INT NOT NULL, teacher_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_1bbe35b53cfc174c39ddb9083c090a95_idx (rev), PRIMARY KEY(program_group_id, teacher_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_specific_training (id INT AUTO_INCREMENT NOT NULL, training_program_id INT NOT NULL, company_id INT DEFAULT NULL, hours INT NOT NULL, name LONGTEXT NOT NULL, additional_company_data LONGTEXT DEFAULT NULL, INDEX IDX_28631CA88406BD6C (training_program_id), INDEX IDX_28631CA8979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_specific_training_audit (id INT NOT NULL, rev INT NOT NULL, training_program_id INT DEFAULT NULL, company_id INT DEFAULT NULL, hours INT DEFAULT NULL, name LONGTEXT DEFAULT NULL, additional_company_data LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_954fda7ced28fa7da330bb57d44d6bae_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_student_learning_program (id INT AUTO_INCREMENT NOT NULL, training_program_group_id INT NOT NULL, student_enrollment_id INT NOT NULL, workcenter_id INT NOT NULL, modality INT NOT NULL, authorization_needed TINYINT(1) NOT NULL, authorization_description LONGTEXT DEFAULT NULL, adaptation_needed TINYINT(1) NOT NULL, adaptation_description LONGTEXT DEFAULT NULL, INDEX IDX_3133C013CEDF9BEF (training_program_group_id), INDEX IDX_3133C013DAE14AC5 (student_enrollment_id), INDEX IDX_3133C013A2473C4B (workcenter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_student_learning_program_audit (id INT NOT NULL, rev INT NOT NULL, training_program_group_id INT DEFAULT NULL, student_enrollment_id INT DEFAULT NULL, workcenter_id INT DEFAULT NULL, modality INT DEFAULT NULL, authorization_needed TINYINT(1) DEFAULT NULL, authorization_description LONGTEXT DEFAULT NULL, adaptation_needed TINYINT(1) DEFAULT NULL, adaptation_description LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_daf610663b684ee243e430f78e762dce_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_training_program (id INT AUTO_INCREMENT NOT NULL, training_id INT NOT NULL, performance_scale_id INT NOT NULL, student_survey_id INT DEFAULT NULL, company_survey_id INT DEFAULT NULL, educational_tutor_survey_id INT DEFAULT NULL, attendance_report_template_id INT DEFAULT NULL, final_report_template_id INT DEFAULT NULL, weekly_activity_report_template_id INT DEFAULT NULL, activity_summary_report_template_id INT DEFAULT NULL, target_hours INT DEFAULT NULL, default_modality INT NOT NULL, locked TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_AF5EDD7BBEFD98D1 (training_id), INDEX IDX_AF5EDD7B7C2C26C0 (performance_scale_id), INDEX IDX_AF5EDD7BD490911D (student_survey_id), INDEX IDX_AF5EDD7B80E5DA6D (company_survey_id), INDEX IDX_AF5EDD7B68F6798B (educational_tutor_survey_id), INDEX IDX_AF5EDD7B13E6472B (attendance_report_template_id), INDEX IDX_AF5EDD7B10BDE131 (final_report_template_id), INDEX IDX_AF5EDD7B3BBF8EEC (weekly_activity_report_template_id), INDEX IDX_AF5EDD7B7791D10 (activity_summary_report_template_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_training_program_audit (id INT NOT NULL, rev INT NOT NULL, training_id INT DEFAULT NULL, performance_scale_id INT DEFAULT NULL, student_survey_id INT DEFAULT NULL, company_survey_id INT DEFAULT NULL, educational_tutor_survey_id INT DEFAULT NULL, attendance_report_template_id INT DEFAULT NULL, final_report_template_id INT DEFAULT NULL, weekly_activity_report_template_id INT DEFAULT NULL, activity_summary_report_template_id INT DEFAULT NULL, target_hours INT DEFAULT NULL, default_modality INT DEFAULT NULL, locked TINYINT(1) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_8ae70b07fee7de0a9454869a1a3e11b9_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_work_day (id INT AUTO_INCREMENT NOT NULL, student_learning_program_id INT NOT NULL, hours INT NOT NULL, date DATE NOT NULL, notes LONGTEXT DEFAULT NULL, other_activities LONGTEXT DEFAULT NULL, locked TINYINT(1) NOT NULL, absence INT NOT NULL, start_time1 VARCHAR(5) DEFAULT NULL, end_time1 VARCHAR(5) DEFAULT NULL, start_time2 VARCHAR(5) DEFAULT NULL, end_time2 VARCHAR(5) DEFAULT NULL, INDEX IDX_219B1BDD2F5D0654 (student_learning_program_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_work_day_activity (work_day_id INT NOT NULL, activity_id INT NOT NULL, INDEX IDX_6712337CA23B8704 (work_day_id), INDEX IDX_6712337C81C06096 (activity_id), PRIMARY KEY(work_day_id, activity_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_work_day_audit (id INT NOT NULL, rev INT NOT NULL, student_learning_program_id INT DEFAULT NULL, hours INT DEFAULT NULL, date DATE DEFAULT NULL, notes LONGTEXT DEFAULT NULL, other_activities LONGTEXT DEFAULT NULL, locked TINYINT(1) DEFAULT NULL, absence INT DEFAULT NULL, start_time1 VARCHAR(5) DEFAULT NULL, end_time1 VARCHAR(5) DEFAULT NULL, start_time2 VARCHAR(5) DEFAULT NULL, end_time2 VARCHAR(5) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_da616dc821dbbf2762747da7f44177b6_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_work_day_activity_audit (work_day_id INT NOT NULL, activity_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_9df7fef31aed011f266d448db6920ead_idx (rev), PRIMARY KEY(work_day_id, activity_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE student_learning_program_activity (id INT AUTO_INCREMENT NOT NULL, activity_id INT NOT NULL, scale_value_id INT DEFAULT NULL, valued_by_id INT DEFAULT NULL, locked TINYINT(1) NOT NULL, details LONGTEXT DEFAULT NULL, INDEX IDX_DAE0CD1581C06096 (activity_id), INDEX IDX_DAE0CD151B925809 (scale_value_id), INDEX IDX_DAE0CD1526F862F5 (valued_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE student_learning_program_activity_audit (id INT NOT NULL, rev INT NOT NULL, activity_id INT DEFAULT NULL, scale_value_id INT DEFAULT NULL, valued_by_id INT DEFAULT NULL, locked TINYINT(1) DEFAULT NULL, details LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_6f7a9491d7075b622485cbbe7a448cc3_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE student_learning_program_activity_comment (id INT AUTO_INCREMENT NOT NULL, student_learning_program_activity_id INT NOT NULL, INDEX IDX_ACAC0E6FA2F2EF0A (student_learning_program_activity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE student_learning_program_activity_comment_audit (id INT NOT NULL, rev INT NOT NULL, student_learning_program_activity_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_8ad5f05d3cb38a4e26b98dd01af0e21e_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE edu_performance_scale ADD CONSTRAINT FK_97FBF6F932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE edu_performance_scale_audit ADD CONSTRAINT rev_872a21aa478051cdedd389213a503b28_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE edu_performance_scale_value ADD CONSTRAINT FK_CB9D66577C2C26C0 FOREIGN KEY (performance_scale_id) REFERENCES edu_performance_scale (id)');
        $this->addSql('ALTER TABLE edu_performance_scale_value_audit ADD CONSTRAINT rev_251f0c8c6a4a618acff9d2ea364b6c7d_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_activity ADD CONSTRAINT FK_12216C8B7F2C5D9D FOREIGN KEY (program_grade_id) REFERENCES itp_program_grade (id)');
        $this->addSql('ALTER TABLE itp_activity_criterion ADD CONSTRAINT FK_5031EC0581C06096 FOREIGN KEY (activity_id) REFERENCES itp_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_activity_criterion ADD CONSTRAINT FK_5031EC0597766307 FOREIGN KEY (criterion_id) REFERENCES edu_criterion (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_activity_audit ADD CONSTRAINT rev_ec81cce9357b10e123ad7a8a61086606_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_company_program ADD CONSTRAINT FK_EA6C82597F2C5D9D FOREIGN KEY (program_grade_id) REFERENCES itp_program_grade (id)');
        $this->addSql('ALTER TABLE itp_company_program ADD CONSTRAINT FK_EA6C8259979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE itp_company_program_activity ADD CONSTRAINT FK_B69227C58336EE9C FOREIGN KEY (company_program_id) REFERENCES itp_company_program (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_company_program_activity ADD CONSTRAINT FK_B69227C581C06096 FOREIGN KEY (activity_id) REFERENCES itp_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_company_program_audit ADD CONSTRAINT rev_2fa1ce202ffe5764db7e79671701410e_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_program_grade ADD CONSTRAINT FK_6F6FE3778406BD6C FOREIGN KEY (training_program_id) REFERENCES itp_training_program (id)');
        $this->addSql('ALTER TABLE itp_program_grade ADD CONSTRAINT FK_6F6FE377FE19A1A8 FOREIGN KEY (grade_id) REFERENCES edu_grade (id)');
        $this->addSql('ALTER TABLE program_grade_subject ADD CONSTRAINT FK_A4CABC007F2C5D9D FOREIGN KEY (program_grade_id) REFERENCES itp_program_grade (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE program_grade_subject ADD CONSTRAINT FK_A4CABC0023EDC87 FOREIGN KEY (subject_id) REFERENCES edu_subject (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_program_grade_audit ADD CONSTRAINT rev_77fa13b589b0147f9bba81f01535097c_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome ADD CONSTRAINT FK_99777B9D7F2C5D9D FOREIGN KEY (program_grade_id) REFERENCES itp_program_grade (id)');
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome ADD CONSTRAINT FK_99777B9D35C2B2D5 FOREIGN KEY (learning_outcome_id) REFERENCES edu_learning_outcome (id)');
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome_audit ADD CONSTRAINT rev_e8feacf1d336f619b32e00ebe3fef4f3_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_program_group ADD CONSTRAINT FK_5BF509867F2C5D9D FOREIGN KEY (program_grade_id) REFERENCES itp_program_grade (id)');
        $this->addSql('ALTER TABLE itp_program_group ADD CONSTRAINT FK_5BF50986FE54D947 FOREIGN KEY (group_id) REFERENCES edu_group (id)');
        $this->addSql('ALTER TABLE itp_program_group_manager ADD CONSTRAINT FK_843C7B3A7F612572 FOREIGN KEY (program_group_id) REFERENCES itp_program_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_program_group_manager ADD CONSTRAINT FK_843C7B3A41807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_program_group_audit ADD CONSTRAINT rev_1bbc384c45dccae2d1b5f0fa90bcce44_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_specific_training ADD CONSTRAINT FK_28631CA88406BD6C FOREIGN KEY (training_program_id) REFERENCES itp_training_program (id)');
        $this->addSql('ALTER TABLE itp_specific_training ADD CONSTRAINT FK_28631CA8979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE itp_specific_training_audit ADD CONSTRAINT rev_954fda7ced28fa7da330bb57d44d6bae_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_student_learning_program ADD CONSTRAINT FK_3133C013CEDF9BEF FOREIGN KEY (training_program_group_id) REFERENCES itp_program_group (id)');
        $this->addSql('ALTER TABLE itp_student_learning_program ADD CONSTRAINT FK_3133C013DAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id)');
        $this->addSql('ALTER TABLE itp_student_learning_program ADD CONSTRAINT FK_3133C013A2473C4B FOREIGN KEY (workcenter_id) REFERENCES workcenter (id)');
        $this->addSql('ALTER TABLE itp_student_learning_program_audit ADD CONSTRAINT rev_daf610663b684ee243e430f78e762dce_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_training_program ADD CONSTRAINT FK_AF5EDD7BBEFD98D1 FOREIGN KEY (training_id) REFERENCES edu_training (id)');
        $this->addSql('ALTER TABLE itp_training_program ADD CONSTRAINT FK_AF5EDD7B7C2C26C0 FOREIGN KEY (performance_scale_id) REFERENCES edu_performance_scale (id)');
        $this->addSql('ALTER TABLE itp_training_program ADD CONSTRAINT FK_AF5EDD7BD490911D FOREIGN KEY (student_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE itp_training_program ADD CONSTRAINT FK_AF5EDD7B80E5DA6D FOREIGN KEY (company_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE itp_training_program ADD CONSTRAINT FK_AF5EDD7B68F6798B FOREIGN KEY (educational_tutor_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE itp_training_program ADD CONSTRAINT FK_AF5EDD7B13E6472B FOREIGN KEY (attendance_report_template_id) REFERENCES edu_report_template (id)');
        $this->addSql('ALTER TABLE itp_training_program ADD CONSTRAINT FK_AF5EDD7B10BDE131 FOREIGN KEY (final_report_template_id) REFERENCES edu_report_template (id)');
        $this->addSql('ALTER TABLE itp_training_program ADD CONSTRAINT FK_AF5EDD7B3BBF8EEC FOREIGN KEY (weekly_activity_report_template_id) REFERENCES edu_report_template (id)');
        $this->addSql('ALTER TABLE itp_training_program ADD CONSTRAINT FK_AF5EDD7B7791D10 FOREIGN KEY (activity_summary_report_template_id) REFERENCES edu_report_template (id)');
        $this->addSql('ALTER TABLE itp_training_program_audit ADD CONSTRAINT rev_8ae70b07fee7de0a9454869a1a3e11b9_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_work_day ADD CONSTRAINT FK_219B1BDD2F5D0654 FOREIGN KEY (student_learning_program_id) REFERENCES itp_student_learning_program (id)');
        $this->addSql('ALTER TABLE itp_work_day_activity ADD CONSTRAINT FK_6712337CA23B8704 FOREIGN KEY (work_day_id) REFERENCES itp_work_day (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_work_day_activity ADD CONSTRAINT FK_6712337C81C06096 FOREIGN KEY (activity_id) REFERENCES itp_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_work_day_audit ADD CONSTRAINT rev_da616dc821dbbf2762747da7f44177b6_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE student_learning_program_activity ADD CONSTRAINT FK_DAE0CD1581C06096 FOREIGN KEY (activity_id) REFERENCES itp_activity (id)');
        $this->addSql('ALTER TABLE student_learning_program_activity ADD CONSTRAINT FK_DAE0CD151B925809 FOREIGN KEY (scale_value_id) REFERENCES edu_performance_scale_value (id)');
        $this->addSql('ALTER TABLE student_learning_program_activity ADD CONSTRAINT FK_DAE0CD1526F862F5 FOREIGN KEY (valued_by_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE student_learning_program_activity_audit ADD CONSTRAINT rev_6f7a9491d7075b622485cbbe7a448cc3_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE student_learning_program_activity_comment ADD CONSTRAINT FK_ACAC0E6FA2F2EF0A FOREIGN KEY (student_learning_program_activity_id) REFERENCES student_learning_program_activity (id)');
        $this->addSql('ALTER TABLE student_learning_program_activity_comment_audit ADD CONSTRAINT rev_8ad5f05d3cb38a4e26b98dd01af0e21e_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wlt_activity_realization_grade DROP FOREIGN KEY FK_CD1FF777166D1F9C');
        $this->addSql('ALTER TABLE wlt_activity_realization_grade_audit DROP FOREIGN KEY rev_f465a2778ab91cc6186229f95700df6f_fk');
        $this->addSql('DROP TABLE wlt_activity_realization_grade');
        $this->addSql('DROP TABLE wlt_activity_realization_grade_audit');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD CONSTRAINT FK_BD86F078FE19A1A8 FOREIGN KEY (grade_id) REFERENCES edu_performance_scale_value (id)');
        $this->addSql('ALTER TABLE wlt_project ADD performance_scale_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wlt_project ADD CONSTRAINT FK_E4D36E417C2C26C0 FOREIGN KEY (performance_scale_id) REFERENCES edu_performance_scale (id)');
        $this->addSql('CREATE INDEX IDX_E4D36E417C2C26C0 ON wlt_project (performance_scale_id)');
        $this->addSql('ALTER TABLE wlt_project_audit ADD performance_scale_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wlt_project DROP FOREIGN KEY FK_E4D36E417C2C26C0');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP FOREIGN KEY FK_BD86F078FE19A1A8');
        $this->addSql('CREATE TABLE wlt_activity_realization_grade (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_spanish_ci`, numeric_grade INT NOT NULL, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_spanish_ci`, INDEX IDX_CD1FF777166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_spanish_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE wlt_activity_realization_grade_audit (id INT NOT NULL, rev INT NOT NULL, project_id INT DEFAULT NULL, description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_spanish_ci`, numeric_grade INT DEFAULT NULL, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_spanish_ci`, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_spanish_ci`, INDEX rev_f465a2778ab91cc6186229f95700df6f_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_spanish_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE wlt_activity_realization_grade ADD CONSTRAINT FK_CD1FF777166D1F9C FOREIGN KEY (project_id) REFERENCES wlt_project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE wlt_activity_realization_grade_audit ADD CONSTRAINT rev_f465a2778ab91cc6186229f95700df6f_fk FOREIGN KEY (rev) REFERENCES revisions (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE edu_performance_scale DROP FOREIGN KEY FK_97FBF6F932C8A3DE');
        $this->addSql('ALTER TABLE edu_performance_scale_audit DROP FOREIGN KEY rev_872a21aa478051cdedd389213a503b28_fk');
        $this->addSql('ALTER TABLE edu_performance_scale_value DROP FOREIGN KEY FK_CB9D66577C2C26C0');
        $this->addSql('ALTER TABLE edu_performance_scale_value_audit DROP FOREIGN KEY rev_251f0c8c6a4a618acff9d2ea364b6c7d_fk');
        $this->addSql('ALTER TABLE itp_activity DROP FOREIGN KEY FK_12216C8B7F2C5D9D');
        $this->addSql('ALTER TABLE itp_activity_criterion DROP FOREIGN KEY FK_5031EC0581C06096');
        $this->addSql('ALTER TABLE itp_activity_criterion DROP FOREIGN KEY FK_5031EC0597766307');
        $this->addSql('ALTER TABLE itp_activity_audit DROP FOREIGN KEY rev_ec81cce9357b10e123ad7a8a61086606_fk');
        $this->addSql('ALTER TABLE itp_company_program DROP FOREIGN KEY FK_EA6C82597F2C5D9D');
        $this->addSql('ALTER TABLE itp_company_program DROP FOREIGN KEY FK_EA6C8259979B1AD6');
        $this->addSql('ALTER TABLE itp_company_program_activity DROP FOREIGN KEY FK_B69227C58336EE9C');
        $this->addSql('ALTER TABLE itp_company_program_activity DROP FOREIGN KEY FK_B69227C581C06096');
        $this->addSql('ALTER TABLE itp_company_program_audit DROP FOREIGN KEY rev_2fa1ce202ffe5764db7e79671701410e_fk');
        $this->addSql('ALTER TABLE itp_program_grade DROP FOREIGN KEY FK_6F6FE3778406BD6C');
        $this->addSql('ALTER TABLE itp_program_grade DROP FOREIGN KEY FK_6F6FE377FE19A1A8');
        $this->addSql('ALTER TABLE program_grade_subject DROP FOREIGN KEY FK_A4CABC007F2C5D9D');
        $this->addSql('ALTER TABLE program_grade_subject DROP FOREIGN KEY FK_A4CABC0023EDC87');
        $this->addSql('ALTER TABLE itp_program_grade_audit DROP FOREIGN KEY rev_77fa13b589b0147f9bba81f01535097c_fk');
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome DROP FOREIGN KEY FK_99777B9D7F2C5D9D');
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome DROP FOREIGN KEY FK_99777B9D35C2B2D5');
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome_audit DROP FOREIGN KEY rev_e8feacf1d336f619b32e00ebe3fef4f3_fk');
        $this->addSql('ALTER TABLE itp_program_group DROP FOREIGN KEY FK_5BF509867F2C5D9D');
        $this->addSql('ALTER TABLE itp_program_group DROP FOREIGN KEY FK_5BF50986FE54D947');
        $this->addSql('ALTER TABLE itp_program_group_manager DROP FOREIGN KEY FK_843C7B3A7F612572');
        $this->addSql('ALTER TABLE itp_program_group_manager DROP FOREIGN KEY FK_843C7B3A41807E1D');
        $this->addSql('ALTER TABLE itp_program_group_audit DROP FOREIGN KEY rev_1bbc384c45dccae2d1b5f0fa90bcce44_fk');
        $this->addSql('ALTER TABLE itp_specific_training DROP FOREIGN KEY FK_28631CA88406BD6C');
        $this->addSql('ALTER TABLE itp_specific_training DROP FOREIGN KEY FK_28631CA8979B1AD6');
        $this->addSql('ALTER TABLE itp_specific_training_audit DROP FOREIGN KEY rev_954fda7ced28fa7da330bb57d44d6bae_fk');
        $this->addSql('ALTER TABLE itp_student_learning_program DROP FOREIGN KEY FK_3133C013CEDF9BEF');
        $this->addSql('ALTER TABLE itp_student_learning_program DROP FOREIGN KEY FK_3133C013DAE14AC5');
        $this->addSql('ALTER TABLE itp_student_learning_program DROP FOREIGN KEY FK_3133C013A2473C4B');
        $this->addSql('ALTER TABLE itp_student_learning_program_audit DROP FOREIGN KEY rev_daf610663b684ee243e430f78e762dce_fk');
        $this->addSql('ALTER TABLE itp_training_program DROP FOREIGN KEY FK_AF5EDD7BBEFD98D1');
        $this->addSql('ALTER TABLE itp_training_program DROP FOREIGN KEY FK_AF5EDD7B7C2C26C0');
        $this->addSql('ALTER TABLE itp_training_program DROP FOREIGN KEY FK_AF5EDD7BD490911D');
        $this->addSql('ALTER TABLE itp_training_program DROP FOREIGN KEY FK_AF5EDD7B80E5DA6D');
        $this->addSql('ALTER TABLE itp_training_program DROP FOREIGN KEY FK_AF5EDD7B68F6798B');
        $this->addSql('ALTER TABLE itp_training_program DROP FOREIGN KEY FK_AF5EDD7B13E6472B');
        $this->addSql('ALTER TABLE itp_training_program DROP FOREIGN KEY FK_AF5EDD7B10BDE131');
        $this->addSql('ALTER TABLE itp_training_program DROP FOREIGN KEY FK_AF5EDD7B3BBF8EEC');
        $this->addSql('ALTER TABLE itp_training_program DROP FOREIGN KEY FK_AF5EDD7B7791D10');
        $this->addSql('ALTER TABLE itp_training_program_audit DROP FOREIGN KEY rev_8ae70b07fee7de0a9454869a1a3e11b9_fk');
        $this->addSql('ALTER TABLE itp_work_day DROP FOREIGN KEY FK_219B1BDD2F5D0654');
        $this->addSql('ALTER TABLE itp_work_day_activity DROP FOREIGN KEY FK_6712337CA23B8704');
        $this->addSql('ALTER TABLE itp_work_day_activity DROP FOREIGN KEY FK_6712337C81C06096');
        $this->addSql('ALTER TABLE itp_work_day_audit DROP FOREIGN KEY rev_da616dc821dbbf2762747da7f44177b6_fk');
        $this->addSql('ALTER TABLE student_learning_program_activity DROP FOREIGN KEY FK_DAE0CD1581C06096');
        $this->addSql('ALTER TABLE student_learning_program_activity DROP FOREIGN KEY FK_DAE0CD151B925809');
        $this->addSql('ALTER TABLE student_learning_program_activity DROP FOREIGN KEY FK_DAE0CD1526F862F5');
        $this->addSql('ALTER TABLE student_learning_program_activity_audit DROP FOREIGN KEY rev_6f7a9491d7075b622485cbbe7a448cc3_fk');
        $this->addSql('ALTER TABLE student_learning_program_activity_comment DROP FOREIGN KEY FK_ACAC0E6FA2F2EF0A');
        $this->addSql('ALTER TABLE student_learning_program_activity_comment_audit DROP FOREIGN KEY rev_8ad5f05d3cb38a4e26b98dd01af0e21e_fk');
        $this->addSql('DROP TABLE edu_performance_scale');
        $this->addSql('DROP TABLE edu_performance_scale_audit');
        $this->addSql('DROP TABLE edu_performance_scale_value');
        $this->addSql('DROP TABLE edu_performance_scale_value_audit');
        $this->addSql('DROP TABLE itp_activity');
        $this->addSql('DROP TABLE itp_activity_criterion');
        $this->addSql('DROP TABLE itp_activity_audit');
        $this->addSql('DROP TABLE itp_activity_criterion_audit');
        $this->addSql('DROP TABLE itp_company_program');
        $this->addSql('DROP TABLE itp_company_program_activity');
        $this->addSql('DROP TABLE itp_company_program_audit');
        $this->addSql('DROP TABLE itp_company_program_activity_audit');
        $this->addSql('DROP TABLE itp_program_grade');
        $this->addSql('DROP TABLE program_grade_subject');
        $this->addSql('DROP TABLE itp_program_grade_audit');
        $this->addSql('DROP TABLE program_grade_subject_audit');
        $this->addSql('DROP TABLE itp_program_grade_learning_outcome');
        $this->addSql('DROP TABLE itp_program_grade_learning_outcome_audit');
        $this->addSql('DROP TABLE itp_program_group');
        $this->addSql('DROP TABLE itp_program_group_manager');
        $this->addSql('DROP TABLE itp_program_group_audit');
        $this->addSql('DROP TABLE itp_program_group_manager_audit');
        $this->addSql('DROP TABLE itp_specific_training');
        $this->addSql('DROP TABLE itp_specific_training_audit');
        $this->addSql('DROP TABLE itp_student_learning_program');
        $this->addSql('DROP TABLE itp_student_learning_program_audit');
        $this->addSql('DROP TABLE itp_training_program');
        $this->addSql('DROP TABLE itp_training_program_audit');
        $this->addSql('DROP TABLE itp_work_day');
        $this->addSql('DROP TABLE itp_work_day_activity');
        $this->addSql('DROP TABLE itp_work_day_audit');
        $this->addSql('DROP TABLE itp_work_day_activity_audit');
        $this->addSql('DROP TABLE student_learning_program_activity');
        $this->addSql('DROP TABLE student_learning_program_activity_audit');
        $this->addSql('DROP TABLE student_learning_program_activity_comment');
        $this->addSql('DROP TABLE student_learning_program_activity_comment_audit');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP FOREIGN KEY FK_BD86F078FE19A1A8');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD CONSTRAINT FK_BD86F078FE19A1A8 FOREIGN KEY (grade_id) REFERENCES wlt_activity_realization_grade (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP INDEX IDX_E4D36E417C2C26C0 ON wlt_project');
        $this->addSql('ALTER TABLE wlt_project DROP performance_scale_id');
        $this->addSql('ALTER TABLE wlt_project_audit DROP performance_scale_id');
    }
}
