<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190115204202 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE edu_teacher (id INT AUTO_INCREMENT NOT NULL, person_id INT NOT NULL, academic_year_id INT NOT NULL, department_id INT DEFAULT NULL, INDEX IDX_89A031C7217BBB47 (person_id), INDEX IDX_89A031C7C54F3401 (academic_year_id), INDEX IDX_89A031C7AE80F5DF (department_id), UNIQUE INDEX UNIQ_89A031C7217BBB47C54F3401 (person_id, academic_year_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_competency (id INT AUTO_INCREMENT NOT NULL, training_id INT NOT NULL, code VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, INDEX IDX_237FAB0BBEFD98D1 (training_id), UNIQUE INDEX UNIQ_237FAB0BBEFD98D177153098 (training_id, code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_group (id INT AUTO_INCREMENT NOT NULL, grade_id INT NOT NULL, internal_code VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_4C368872FE19A1A8 (grade_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_group_tutor (group_id INT NOT NULL, teacher_id INT NOT NULL, INDEX IDX_78832E94FE54D947 (group_id), INDEX IDX_78832E9441807E1D (teacher_id), PRIMARY KEY(group_id, teacher_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_grade (id INT AUTO_INCREMENT NOT NULL, training_id INT NOT NULL, internal_code VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_78AC6283BEFD98D1 (training_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_student_enrollment (id INT AUTO_INCREMENT NOT NULL, person_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_7824A430217BBB47 (person_id), INDEX IDX_7824A430FE54D947 (group_id), UNIQUE INDEX UNIQ_7824A430217BBB47FE54D947 (person_id, group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_teaching (id INT AUTO_INCREMENT NOT NULL, teacher_id INT NOT NULL, group_id INT NOT NULL, subject_id INT NOT NULL, work_linked TINYINT(1) NOT NULL, INDEX IDX_EB30486D41807E1D (teacher_id), INDEX IDX_EB30486DFE54D947 (group_id), INDEX IDX_EB30486D23EDC87 (subject_id), UNIQUE INDEX UNIQ_EB30486D41807E1DFE54D94723EDC87 (teacher_id, group_id, subject_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_non_working_day (id INT AUTO_INCREMENT NOT NULL, academic_year_id INT NOT NULL, date DATE NOT NULL, description VARCHAR(255) DEFAULT NULL, INDEX IDX_505655C0C54F3401 (academic_year_id), UNIQUE INDEX UNIQ_505655C0C54F3401AA9E377A (academic_year_id, date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_department (id INT AUTO_INCREMENT NOT NULL, academic_year_id INT NOT NULL, head_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, internal_code VARCHAR(255) DEFAULT NULL, INDEX IDX_6EB77EB1C54F3401 (academic_year_id), INDEX IDX_6EB77EB1F41A619E (head_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_learning_outcome (id INT AUTO_INCREMENT NOT NULL, subject_id INT NOT NULL, code VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, INDEX IDX_301888C623EDC87 (subject_id), UNIQUE INDEX UNIQ_301888C623EDC8777153098 (subject_id, code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_training (id INT AUTO_INCREMENT NOT NULL, academic_year_id INT NOT NULL, department_id INT DEFAULT NULL, internal_code VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, work_linked TINYINT(1) NOT NULL, INDEX IDX_2692AD50C54F3401 (academic_year_id), INDEX IDX_2692AD50AE80F5DF (department_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_subject (id INT AUTO_INCREMENT NOT NULL, grade_id INT NOT NULL, code VARCHAR(255) DEFAULT NULL, internal_code VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, workplace_training TINYINT(1) NOT NULL, INDEX IDX_C298A968FE19A1A8 (grade_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_academic_year (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, principal_id INT DEFAULT NULL, financial_manager_id INT DEFAULT NULL, description VARCHAR(255) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, INDEX IDX_CFBE31D032C8A3DE (organization_id), INDEX IDX_CFBE31D0474870EE (principal_id), INDEX IDX_CFBE31D0FD5CC44A (financial_manager_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, default_organization_id INT DEFAULT NULL, login_username VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, enabled TINYINT(1) NOT NULL, global_administrator TINYINT(1) NOT NULL, email_address VARCHAR(255) DEFAULT NULL, token VARCHAR(255) DEFAULT NULL, token_type VARCHAR(255) DEFAULT NULL, token_expiration DATETIME DEFAULT NULL, last_access DATETIME DEFAULT NULL, blocked_until DATETIME DEFAULT NULL, external_check TINYINT(1) NOT NULL, allow_external_check TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649D6FA26E8 (login_username), UNIQUE INDEX UNIQ_8D93D64935C246D5 (password), UNIQUE INDEX UNIQ_8D93D649B08E074E (email_address), INDEX IDX_8D93D649AA9E0B02 (default_organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, person_id INT DEFAULT NULL, organization_id INT DEFAULT NULL, role VARCHAR(20) NOT NULL, INDEX IDX_57698A6A217BBB47 (person_id), INDEX IDX_57698A6A32C8A3DE (organization_id), UNIQUE INDEX UNIQ_57698A6A217BBB4732C8A3DE57698A6A (person_id, organization_id, role), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE person (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, internal_code VARCHAR(255) DEFAULT NULL, unique_identifier VARCHAR(255) DEFAULT NULL, gender INT NOT NULL, UNIQUE INDEX UNIQ_34DCD1766BD2BEA0 (unique_identifier), UNIQUE INDEX UNIQ_34DCD176A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE company (id INT AUTO_INCREMENT NOT NULL, manager_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(255) NOT NULL, zip_code VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, fax_number VARCHAR(255) DEFAULT NULL, email_address VARCHAR(255) DEFAULT NULL, web_site VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_4FBF094F77153098 (code), INDEX IDX_4FBF094F783E3463 (manager_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workcenter (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, academic_year_id INT NOT NULL, manager_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(255) NOT NULL, zip_code VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, fax_number VARCHAR(255) DEFAULT NULL, email_address VARCHAR(255) DEFAULT NULL, INDEX IDX_E2337C97979B1AD6 (company_id), INDEX IDX_E2337C97C54F3401 (academic_year_id), INDEX IDX_E2337C97783E3463 (manager_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE membership (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, organization_id INT NOT NULL, valid_from DATETIME NOT NULL, valid_until DATETIME DEFAULT NULL, INDEX IDX_86FFD285A76ED395 (user_id), INDEX IDX_86FFD28532C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_activity_realization (id INT AUTO_INCREMENT NOT NULL, activity_id INT NOT NULL, code VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, INDEX IDX_BA14088981C06096 (activity_id), UNIQUE INDEX UNIQ_BA14088981C0609677153098 (activity_id, code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_activity_realization_learning_outcome (activity_realization_id INT NOT NULL, learning_outcome_id INT NOT NULL, INDEX IDX_C17FFB1E862E876A (activity_realization_id), INDEX IDX_C17FFB1E35C2B2D5 (learning_outcome_id), PRIMARY KEY(activity_realization_id, learning_outcome_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_meeting (id INT AUTO_INCREMENT NOT NULL, academic_year_id INT NOT NULL, created_by_id INT NOT NULL, date_time DATETIME NOT NULL, detail LONGTEXT DEFAULT NULL, INDEX IDX_3E755F96C54F3401 (academic_year_id), INDEX IDX_3E755F96B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_meeting_student_enrollment (meeting_id INT NOT NULL, student_enrollment_id INT NOT NULL, INDEX IDX_A19B8C2067433D9C (meeting_id), INDEX IDX_A19B8C20DAE14AC5 (student_enrollment_id), PRIMARY KEY(meeting_id, student_enrollment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_meeting_teacher (meeting_id INT NOT NULL, teacher_id INT NOT NULL, INDEX IDX_BD32F91567433D9C (meeting_id), INDEX IDX_BD32F91541807E1D (teacher_id), PRIMARY KEY(meeting_id, teacher_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_agreement_activity_realization (id INT AUTO_INCREMENT NOT NULL, agreement_id INT NOT NULL, activity_realization_id INT NOT NULL, grade_id INT DEFAULT NULL, graded_by_id INT DEFAULT NULL, graded_on DATE DEFAULT NULL, INDEX IDX_BD86F07824890B2B (agreement_id), INDEX IDX_BD86F078862E876A (activity_realization_id), INDEX IDX_BD86F078FE19A1A8 (grade_id), INDEX IDX_BD86F078C814BC2E (graded_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_activity (id INT AUTO_INCREMENT NOT NULL, subject_id INT NOT NULL, code VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, prior_learning LONGTEXT DEFAULT NULL, INDEX IDX_EAD6D79D23EDC87 (subject_id), UNIQUE INDEX UNIQ_EAD6D79D23EDC8777153098 (subject_id, code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_activity_competency (activity_id INT NOT NULL, competency_id INT NOT NULL, INDEX IDX_39DDED3181C06096 (activity_id), INDEX IDX_39DDED31FB9F58C (competency_id), PRIMARY KEY(activity_id, competency_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_work_day (id INT AUTO_INCREMENT NOT NULL, agreement_id INT NOT NULL, hours INT NOT NULL, date DATE NOT NULL, notes LONGTEXT DEFAULT NULL, locked TINYINT(1) NOT NULL, absence INT NOT NULL, start_time1 VARCHAR(5) DEFAULT NULL, end_time1 VARCHAR(5) DEFAULT NULL, start_time2 VARCHAR(5) DEFAULT NULL, end_time2 VARCHAR(5) DEFAULT NULL, INDEX IDX_D96CA0CB24890B2B (agreement_id), UNIQUE INDEX UNIQ_D96CA0CB24890B2BAA9E377A (agreement_id, date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_tracking (work_day_id INT NOT NULL, activity_realization_id INT NOT NULL, INDEX IDX_EEDEBCDBA23B8704 (work_day_id), INDEX IDX_EEDEBCDB862E876A (activity_realization_id), PRIMARY KEY(work_day_id, activity_realization_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_visit (id INT AUTO_INCREMENT NOT NULL, teacher_id INT NOT NULL, workcenter_id INT NOT NULL, date_time DATETIME NOT NULL, detail LONGTEXT DEFAULT NULL, INDEX IDX_952C5F7841807E1D (teacher_id), INDEX IDX_952C5F78A2473C4B (workcenter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_agreement (id INT AUTO_INCREMENT NOT NULL, workcenter_id INT NOT NULL, student_enrollment_id INT NOT NULL, work_tutor_id INT NOT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, student_poll_submitted TINYINT(1) NOT NULL, default_start_time1 VARCHAR(5) DEFAULT NULL, default_end_time1 VARCHAR(5) DEFAULT NULL, default_start_time2 VARCHAR(5) DEFAULT NULL, default_end_time2 VARCHAR(5) DEFAULT NULL, INDEX IDX_2B23AFE9A2473C4B (workcenter_id), INDEX IDX_2B23AFE9DAE14AC5 (student_enrollment_id), INDEX IDX_2B23AFE9F53AEEAD (work_tutor_id), UNIQUE INDEX UNIQ_2B23AFE9DAE14AC5A2473C4B (student_enrollment_id, workcenter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_learning_program (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, training_id INT NOT NULL, INDEX IDX_5EB2E44D979B1AD6 (company_id), INDEX IDX_5EB2E44DBEFD98D1 (training_id), UNIQUE INDEX UNIQ_5EB2E44D979B1AD6BEFD98D1 (company_id, training_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_learning_program_activity_realization (learning_program_id INT NOT NULL, activity_realization_id INT NOT NULL, INDEX IDX_7EADE17BED94D8BC (learning_program_id), INDEX IDX_7EADE17B862E876A (activity_realization_id), PRIMARY KEY(learning_program_id, activity_realization_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_activity_realization_grade (id INT AUTO_INCREMENT NOT NULL, academic_year_id INT NOT NULL, description VARCHAR(255) NOT NULL, numeric_grade INT NOT NULL, notes LONGTEXT DEFAULT NULL, INDEX IDX_CD1FF777C54F3401 (academic_year_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE organization (id INT AUTO_INCREMENT NOT NULL, current_academic_year_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(255) NOT NULL, zip_code VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, fax_number VARCHAR(255) DEFAULT NULL, email_address VARCHAR(255) DEFAULT NULL, web_site VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_C1EE637C77153098 (code), UNIQUE INDEX UNIQ_C1EE637C2B06A9F7 (current_academic_year_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE edu_teacher ADD CONSTRAINT FK_89A031C7217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE edu_teacher ADD CONSTRAINT FK_89A031C7C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE edu_teacher ADD CONSTRAINT FK_89A031C7AE80F5DF FOREIGN KEY (department_id) REFERENCES edu_department (id)');
        $this->addSql('ALTER TABLE edu_competency ADD CONSTRAINT FK_237FAB0BBEFD98D1 FOREIGN KEY (training_id) REFERENCES edu_training (id)');
        $this->addSql('ALTER TABLE edu_group ADD CONSTRAINT FK_4C368872FE19A1A8 FOREIGN KEY (grade_id) REFERENCES edu_grade (id)');
        $this->addSql('ALTER TABLE edu_group_tutor ADD CONSTRAINT FK_78832E94FE54D947 FOREIGN KEY (group_id) REFERENCES edu_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE edu_group_tutor ADD CONSTRAINT FK_78832E9441807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE edu_grade ADD CONSTRAINT FK_78AC6283BEFD98D1 FOREIGN KEY (training_id) REFERENCES edu_training (id)');
        $this->addSql('ALTER TABLE edu_student_enrollment ADD CONSTRAINT FK_7824A430217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE edu_student_enrollment ADD CONSTRAINT FK_7824A430FE54D947 FOREIGN KEY (group_id) REFERENCES edu_group (id)');
        $this->addSql('ALTER TABLE edu_teaching ADD CONSTRAINT FK_EB30486D41807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE edu_teaching ADD CONSTRAINT FK_EB30486DFE54D947 FOREIGN KEY (group_id) REFERENCES edu_group (id)');
        $this->addSql('ALTER TABLE edu_teaching ADD CONSTRAINT FK_EB30486D23EDC87 FOREIGN KEY (subject_id) REFERENCES edu_subject (id)');
        $this->addSql('ALTER TABLE edu_non_working_day ADD CONSTRAINT FK_505655C0C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE edu_department ADD CONSTRAINT FK_6EB77EB1C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE edu_department ADD CONSTRAINT FK_6EB77EB1F41A619E FOREIGN KEY (head_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE edu_learning_outcome ADD CONSTRAINT FK_301888C623EDC87 FOREIGN KEY (subject_id) REFERENCES edu_subject (id)');
        $this->addSql('ALTER TABLE edu_training ADD CONSTRAINT FK_2692AD50C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE edu_training ADD CONSTRAINT FK_2692AD50AE80F5DF FOREIGN KEY (department_id) REFERENCES edu_department (id)');
        $this->addSql('ALTER TABLE edu_subject ADD CONSTRAINT FK_C298A968FE19A1A8 FOREIGN KEY (grade_id) REFERENCES edu_grade (id)');
        $this->addSql('ALTER TABLE edu_academic_year ADD CONSTRAINT FK_CFBE31D032C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE edu_academic_year ADD CONSTRAINT FK_CFBE31D0474870EE FOREIGN KEY (principal_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE edu_academic_year ADD CONSTRAINT FK_CFBE31D0FD5CC44A FOREIGN KEY (financial_manager_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649AA9E0B02 FOREIGN KEY (default_organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE role ADD CONSTRAINT FK_57698A6A217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE role ADD CONSTRAINT FK_57698A6A32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE person ADD CONSTRAINT FK_34DCD176A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE company ADD CONSTRAINT FK_4FBF094F783E3463 FOREIGN KEY (manager_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE workcenter ADD CONSTRAINT FK_E2337C97979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE workcenter ADD CONSTRAINT FK_E2337C97C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE workcenter ADD CONSTRAINT FK_E2337C97783E3463 FOREIGN KEY (manager_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD285A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD28532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE wlt_activity_realization ADD CONSTRAINT FK_BA14088981C06096 FOREIGN KEY (activity_id) REFERENCES wlt_activity (id)');
        $this->addSql('ALTER TABLE wlt_activity_realization_learning_outcome ADD CONSTRAINT FK_C17FFB1E862E876A FOREIGN KEY (activity_realization_id) REFERENCES wlt_activity_realization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_activity_realization_learning_outcome ADD CONSTRAINT FK_C17FFB1E35C2B2D5 FOREIGN KEY (learning_outcome_id) REFERENCES edu_learning_outcome (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_meeting ADD CONSTRAINT FK_3E755F96C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE wlt_meeting ADD CONSTRAINT FK_3E755F96B03A8386 FOREIGN KEY (created_by_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE wlt_meeting_student_enrollment ADD CONSTRAINT FK_A19B8C2067433D9C FOREIGN KEY (meeting_id) REFERENCES wlt_meeting (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_meeting_student_enrollment ADD CONSTRAINT FK_A19B8C20DAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_meeting_teacher ADD CONSTRAINT FK_BD32F91567433D9C FOREIGN KEY (meeting_id) REFERENCES wlt_meeting (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_meeting_teacher ADD CONSTRAINT FK_BD32F91541807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD CONSTRAINT FK_BD86F07824890B2B FOREIGN KEY (agreement_id) REFERENCES wlt_agreement (id)');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD CONSTRAINT FK_BD86F078862E876A FOREIGN KEY (activity_realization_id) REFERENCES wlt_activity_realization (id)');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD CONSTRAINT FK_BD86F078FE19A1A8 FOREIGN KEY (grade_id) REFERENCES wlt_activity_realization_grade (id)');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD CONSTRAINT FK_BD86F078C814BC2E FOREIGN KEY (graded_by_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE wlt_activity ADD CONSTRAINT FK_EAD6D79D23EDC87 FOREIGN KEY (subject_id) REFERENCES edu_subject (id)');
        $this->addSql('ALTER TABLE wlt_activity_competency ADD CONSTRAINT FK_39DDED3181C06096 FOREIGN KEY (activity_id) REFERENCES wlt_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_activity_competency ADD CONSTRAINT FK_39DDED31FB9F58C FOREIGN KEY (competency_id) REFERENCES edu_competency (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_work_day ADD CONSTRAINT FK_D96CA0CB24890B2B FOREIGN KEY (agreement_id) REFERENCES wlt_agreement (id)');
        $this->addSql('ALTER TABLE wlt_tracking ADD CONSTRAINT FK_EEDEBCDBA23B8704 FOREIGN KEY (work_day_id) REFERENCES wlt_work_day (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_tracking ADD CONSTRAINT FK_EEDEBCDB862E876A FOREIGN KEY (activity_realization_id) REFERENCES wlt_activity_realization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_visit ADD CONSTRAINT FK_952C5F7841807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE wlt_visit ADD CONSTRAINT FK_952C5F78A2473C4B FOREIGN KEY (workcenter_id) REFERENCES workcenter (id)');
        $this->addSql('ALTER TABLE wlt_agreement ADD CONSTRAINT FK_2B23AFE9A2473C4B FOREIGN KEY (workcenter_id) REFERENCES workcenter (id)');
        $this->addSql('ALTER TABLE wlt_agreement ADD CONSTRAINT FK_2B23AFE9DAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id)');
        $this->addSql('ALTER TABLE wlt_agreement ADD CONSTRAINT FK_2B23AFE9F53AEEAD FOREIGN KEY (work_tutor_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE wlt_learning_program ADD CONSTRAINT FK_5EB2E44D979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE wlt_learning_program ADD CONSTRAINT FK_5EB2E44DBEFD98D1 FOREIGN KEY (training_id) REFERENCES edu_training (id)');
        $this->addSql('ALTER TABLE wlt_learning_program_activity_realization ADD CONSTRAINT FK_7EADE17BED94D8BC FOREIGN KEY (learning_program_id) REFERENCES wlt_learning_program (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_learning_program_activity_realization ADD CONSTRAINT FK_7EADE17B862E876A FOREIGN KEY (activity_realization_id) REFERENCES wlt_activity_realization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_activity_realization_grade ADD CONSTRAINT FK_CD1FF777C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C2B06A9F7 FOREIGN KEY (current_academic_year_id) REFERENCES edu_academic_year (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edu_group_tutor DROP FOREIGN KEY FK_78832E9441807E1D');
        $this->addSql('ALTER TABLE edu_teaching DROP FOREIGN KEY FK_EB30486D41807E1D');
        $this->addSql('ALTER TABLE edu_department DROP FOREIGN KEY FK_6EB77EB1F41A619E');
        $this->addSql('ALTER TABLE edu_academic_year DROP FOREIGN KEY FK_CFBE31D0474870EE');
        $this->addSql('ALTER TABLE edu_academic_year DROP FOREIGN KEY FK_CFBE31D0FD5CC44A');
        $this->addSql('ALTER TABLE wlt_meeting DROP FOREIGN KEY FK_3E755F96B03A8386');
        $this->addSql('ALTER TABLE wlt_meeting_teacher DROP FOREIGN KEY FK_BD32F91541807E1D');
        $this->addSql('ALTER TABLE wlt_visit DROP FOREIGN KEY FK_952C5F7841807E1D');
        $this->addSql('ALTER TABLE wlt_activity_competency DROP FOREIGN KEY FK_39DDED31FB9F58C');
        $this->addSql('ALTER TABLE edu_group_tutor DROP FOREIGN KEY FK_78832E94FE54D947');
        $this->addSql('ALTER TABLE edu_student_enrollment DROP FOREIGN KEY FK_7824A430FE54D947');
        $this->addSql('ALTER TABLE edu_teaching DROP FOREIGN KEY FK_EB30486DFE54D947');
        $this->addSql('ALTER TABLE edu_group DROP FOREIGN KEY FK_4C368872FE19A1A8');
        $this->addSql('ALTER TABLE edu_subject DROP FOREIGN KEY FK_C298A968FE19A1A8');
        $this->addSql('ALTER TABLE wlt_meeting_student_enrollment DROP FOREIGN KEY FK_A19B8C20DAE14AC5');
        $this->addSql('ALTER TABLE wlt_agreement DROP FOREIGN KEY FK_2B23AFE9DAE14AC5');
        $this->addSql('ALTER TABLE edu_teacher DROP FOREIGN KEY FK_89A031C7AE80F5DF');
        $this->addSql('ALTER TABLE edu_training DROP FOREIGN KEY FK_2692AD50AE80F5DF');
        $this->addSql('ALTER TABLE wlt_activity_realization_learning_outcome DROP FOREIGN KEY FK_C17FFB1E35C2B2D5');
        $this->addSql('ALTER TABLE edu_competency DROP FOREIGN KEY FK_237FAB0BBEFD98D1');
        $this->addSql('ALTER TABLE edu_grade DROP FOREIGN KEY FK_78AC6283BEFD98D1');
        $this->addSql('ALTER TABLE wlt_learning_program DROP FOREIGN KEY FK_5EB2E44DBEFD98D1');
        $this->addSql('ALTER TABLE edu_teaching DROP FOREIGN KEY FK_EB30486D23EDC87');
        $this->addSql('ALTER TABLE edu_learning_outcome DROP FOREIGN KEY FK_301888C623EDC87');
        $this->addSql('ALTER TABLE wlt_activity DROP FOREIGN KEY FK_EAD6D79D23EDC87');
        $this->addSql('ALTER TABLE edu_teacher DROP FOREIGN KEY FK_89A031C7C54F3401');
        $this->addSql('ALTER TABLE edu_non_working_day DROP FOREIGN KEY FK_505655C0C54F3401');
        $this->addSql('ALTER TABLE edu_department DROP FOREIGN KEY FK_6EB77EB1C54F3401');
        $this->addSql('ALTER TABLE edu_training DROP FOREIGN KEY FK_2692AD50C54F3401');
        $this->addSql('ALTER TABLE workcenter DROP FOREIGN KEY FK_E2337C97C54F3401');
        $this->addSql('ALTER TABLE wlt_meeting DROP FOREIGN KEY FK_3E755F96C54F3401');
        $this->addSql('ALTER TABLE wlt_activity_realization_grade DROP FOREIGN KEY FK_CD1FF777C54F3401');
        $this->addSql('ALTER TABLE organization DROP FOREIGN KEY FK_C1EE637C2B06A9F7');
        $this->addSql('ALTER TABLE person DROP FOREIGN KEY FK_34DCD176A76ED395');
        $this->addSql('ALTER TABLE membership DROP FOREIGN KEY FK_86FFD285A76ED395');
        $this->addSql('ALTER TABLE edu_teacher DROP FOREIGN KEY FK_89A031C7217BBB47');
        $this->addSql('ALTER TABLE edu_student_enrollment DROP FOREIGN KEY FK_7824A430217BBB47');
        $this->addSql('ALTER TABLE role DROP FOREIGN KEY FK_57698A6A217BBB47');
        $this->addSql('ALTER TABLE company DROP FOREIGN KEY FK_4FBF094F783E3463');
        $this->addSql('ALTER TABLE workcenter DROP FOREIGN KEY FK_E2337C97783E3463');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP FOREIGN KEY FK_BD86F078C814BC2E');
        $this->addSql('ALTER TABLE wlt_agreement DROP FOREIGN KEY FK_2B23AFE9F53AEEAD');
        $this->addSql('ALTER TABLE workcenter DROP FOREIGN KEY FK_E2337C97979B1AD6');
        $this->addSql('ALTER TABLE wlt_learning_program DROP FOREIGN KEY FK_5EB2E44D979B1AD6');
        $this->addSql('ALTER TABLE wlt_visit DROP FOREIGN KEY FK_952C5F78A2473C4B');
        $this->addSql('ALTER TABLE wlt_agreement DROP FOREIGN KEY FK_2B23AFE9A2473C4B');
        $this->addSql('ALTER TABLE wlt_activity_realization_learning_outcome DROP FOREIGN KEY FK_C17FFB1E862E876A');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP FOREIGN KEY FK_BD86F078862E876A');
        $this->addSql('ALTER TABLE wlt_tracking DROP FOREIGN KEY FK_EEDEBCDB862E876A');
        $this->addSql('ALTER TABLE wlt_learning_program_activity_realization DROP FOREIGN KEY FK_7EADE17B862E876A');
        $this->addSql('ALTER TABLE wlt_meeting_student_enrollment DROP FOREIGN KEY FK_A19B8C2067433D9C');
        $this->addSql('ALTER TABLE wlt_meeting_teacher DROP FOREIGN KEY FK_BD32F91567433D9C');
        $this->addSql('ALTER TABLE wlt_activity_realization DROP FOREIGN KEY FK_BA14088981C06096');
        $this->addSql('ALTER TABLE wlt_activity_competency DROP FOREIGN KEY FK_39DDED3181C06096');
        $this->addSql('ALTER TABLE wlt_tracking DROP FOREIGN KEY FK_EEDEBCDBA23B8704');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP FOREIGN KEY FK_BD86F07824890B2B');
        $this->addSql('ALTER TABLE wlt_work_day DROP FOREIGN KEY FK_D96CA0CB24890B2B');
        $this->addSql('ALTER TABLE wlt_learning_program_activity_realization DROP FOREIGN KEY FK_7EADE17BED94D8BC');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP FOREIGN KEY FK_BD86F078FE19A1A8');
        $this->addSql('ALTER TABLE edu_academic_year DROP FOREIGN KEY FK_CFBE31D032C8A3DE');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649AA9E0B02');
        $this->addSql('ALTER TABLE role DROP FOREIGN KEY FK_57698A6A32C8A3DE');
        $this->addSql('ALTER TABLE membership DROP FOREIGN KEY FK_86FFD28532C8A3DE');
        $this->addSql('DROP TABLE edu_teacher');
        $this->addSql('DROP TABLE edu_competency');
        $this->addSql('DROP TABLE edu_group');
        $this->addSql('DROP TABLE edu_group_tutor');
        $this->addSql('DROP TABLE edu_grade');
        $this->addSql('DROP TABLE edu_student_enrollment');
        $this->addSql('DROP TABLE edu_teaching');
        $this->addSql('DROP TABLE edu_non_working_day');
        $this->addSql('DROP TABLE edu_department');
        $this->addSql('DROP TABLE edu_learning_outcome');
        $this->addSql('DROP TABLE edu_training');
        $this->addSql('DROP TABLE edu_subject');
        $this->addSql('DROP TABLE edu_academic_year');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE person');
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP TABLE workcenter');
        $this->addSql('DROP TABLE membership');
        $this->addSql('DROP TABLE wlt_activity_realization');
        $this->addSql('DROP TABLE wlt_activity_realization_learning_outcome');
        $this->addSql('DROP TABLE wlt_meeting');
        $this->addSql('DROP TABLE wlt_meeting_student_enrollment');
        $this->addSql('DROP TABLE wlt_meeting_teacher');
        $this->addSql('DROP TABLE wlt_agreement_activity_realization');
        $this->addSql('DROP TABLE wlt_activity');
        $this->addSql('DROP TABLE wlt_activity_competency');
        $this->addSql('DROP TABLE wlt_work_day');
        $this->addSql('DROP TABLE wlt_tracking');
        $this->addSql('DROP TABLE wlt_visit');
        $this->addSql('DROP TABLE wlt_agreement');
        $this->addSql('DROP TABLE wlt_learning_program');
        $this->addSql('DROP TABLE wlt_learning_program_activity_realization');
        $this->addSql('DROP TABLE wlt_activity_realization_grade');
        $this->addSql('DROP TABLE organization');
    }
}
