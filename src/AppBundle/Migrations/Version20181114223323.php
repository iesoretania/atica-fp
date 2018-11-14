<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181114223323 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE company (id INT AUTO_INCREMENT NOT NULL, manager_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(255) NOT NULL, zip_code VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, fax_number VARCHAR(255) DEFAULT NULL, email_address VARCHAR(255) DEFAULT NULL, web_site VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_4FBF094F77153098 (code), INDEX IDX_4FBF094F783E3463 (manager_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_academic_year (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, principal_id INT DEFAULT NULL, financial_manager_id INT DEFAULT NULL, description VARCHAR(255) NOT NULL, INDEX IDX_CFBE31D032C8A3DE (organization_id), INDEX IDX_CFBE31D0474870EE (principal_id), INDEX IDX_CFBE31D0FD5CC44A (financial_manager_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_competency (id INT AUTO_INCREMENT NOT NULL, training_id INT NOT NULL, code VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, INDEX IDX_237FAB0BBEFD98D1 (training_id), UNIQUE INDEX UNIQ_237FAB0BBEFD98D177153098 (training_id, code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_department (id INT AUTO_INCREMENT NOT NULL, academic_year_id INT NOT NULL, head_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_6EB77EB1C54F3401 (academic_year_id), INDEX IDX_6EB77EB1F41A619E (head_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_grade (id INT AUTO_INCREMENT NOT NULL, training_id INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_78AC6283BEFD98D1 (training_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_group (id INT AUTO_INCREMENT NOT NULL, grade_id INT NOT NULL, tutor_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_4C368872FE19A1A8 (grade_id), INDEX IDX_4C368872208F64F1 (tutor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_learning_outcome (id INT AUTO_INCREMENT NOT NULL, subject_id INT NOT NULL, code VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, INDEX IDX_301888C623EDC87 (subject_id), UNIQUE INDEX UNIQ_301888C623EDC8777153098 (subject_id, code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE student_enrollment (id INT AUTO_INCREMENT NOT NULL, person_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_36033A00217BBB47 (person_id), INDEX IDX_36033A00FE54D947 (group_id), UNIQUE INDEX UNIQ_36033A00217BBB47FE54D947 (person_id, group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_subject (id INT AUTO_INCREMENT NOT NULL, grade_id INT NOT NULL, code VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_C298A968FE19A1A8 (grade_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_teacher (id INT AUTO_INCREMENT NOT NULL, person_id INT NOT NULL, academic_year_id INT NOT NULL, department_id INT DEFAULT NULL, INDEX IDX_89A031C7217BBB47 (person_id), INDEX IDX_89A031C7C54F3401 (academic_year_id), INDEX IDX_89A031C7AE80F5DF (department_id), UNIQUE INDEX UNIQ_89A031C7217BBB47C54F3401 (person_id, academic_year_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_teaching (teacher_id INT NOT NULL, group_id INT NOT NULL, subject_id INT NOT NULL, INDEX IDX_EB30486D41807E1D (teacher_id), INDEX IDX_EB30486DFE54D947 (group_id), INDEX IDX_EB30486D23EDC87 (subject_id), PRIMARY KEY(teacher_id, group_id, subject_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_training (id INT AUTO_INCREMENT NOT NULL, academic_year_id INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_2692AD50C54F3401 (academic_year_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE membership (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, organization_id INT NOT NULL, valid_from DATETIME NOT NULL, valid_until DATETIME DEFAULT NULL, INDEX IDX_86FFD285A76ED395 (user_id), INDEX IDX_86FFD28532C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE organization (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(255) NOT NULL, zip_code VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, fax_number VARCHAR(255) DEFAULT NULL, email_address VARCHAR(255) DEFAULT NULL, web_site VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_C1EE637C77153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE manager (organization_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_FA2425B932C8A3DE (organization_id), INDEX IDX_FA2425B9A76ED395 (user_id), PRIMARY KEY(organization_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE person (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, internal_code VARCHAR(255) DEFAULT NULL, gender INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, person_id INT DEFAULT NULL, default_organization_id INT DEFAULT NULL, login_username VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, enabled TINYINT(1) NOT NULL, global_administrator TINYINT(1) NOT NULL, email_address VARCHAR(255) DEFAULT NULL, token VARCHAR(255) DEFAULT NULL, token_type VARCHAR(255) DEFAULT NULL, token_expiration DATETIME DEFAULT NULL, last_access DATETIME DEFAULT NULL, blocked_until DATETIME DEFAULT NULL, external_check TINYINT(1) NOT NULL, allow_external_check TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649D6FA26E8 (login_username), UNIQUE INDEX UNIQ_8D93D64935C246D5 (password), UNIQUE INDEX UNIQ_8D93D649B08E074E (email_address), UNIQUE INDEX UNIQ_8D93D649217BBB47 (person_id), INDEX IDX_8D93D649AA9E0B02 (default_organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workcenter (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, manager_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(255) NOT NULL, zip_code VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, fax_number VARCHAR(255) DEFAULT NULL, email_address VARCHAR(255) DEFAULT NULL, INDEX IDX_E2337C97979B1AD6 (company_id), INDEX IDX_E2337C97783E3463 (manager_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE company ADD CONSTRAINT FK_4FBF094F783E3463 FOREIGN KEY (manager_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE edu_academic_year ADD CONSTRAINT FK_CFBE31D032C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE edu_academic_year ADD CONSTRAINT FK_CFBE31D0474870EE FOREIGN KEY (principal_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE edu_academic_year ADD CONSTRAINT FK_CFBE31D0FD5CC44A FOREIGN KEY (financial_manager_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE edu_competency ADD CONSTRAINT FK_237FAB0BBEFD98D1 FOREIGN KEY (training_id) REFERENCES edu_training (id)');
        $this->addSql('ALTER TABLE edu_department ADD CONSTRAINT FK_6EB77EB1C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE edu_department ADD CONSTRAINT FK_6EB77EB1F41A619E FOREIGN KEY (head_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE edu_grade ADD CONSTRAINT FK_78AC6283BEFD98D1 FOREIGN KEY (training_id) REFERENCES edu_training (id)');
        $this->addSql('ALTER TABLE edu_group ADD CONSTRAINT FK_4C368872FE19A1A8 FOREIGN KEY (grade_id) REFERENCES edu_grade (id)');
        $this->addSql('ALTER TABLE edu_group ADD CONSTRAINT FK_4C368872208F64F1 FOREIGN KEY (tutor_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE edu_learning_outcome ADD CONSTRAINT FK_301888C623EDC87 FOREIGN KEY (subject_id) REFERENCES edu_subject (id)');
        $this->addSql('ALTER TABLE student_enrollment ADD CONSTRAINT FK_36033A00217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE student_enrollment ADD CONSTRAINT FK_36033A00FE54D947 FOREIGN KEY (group_id) REFERENCES edu_group (id)');
        $this->addSql('ALTER TABLE edu_subject ADD CONSTRAINT FK_C298A968FE19A1A8 FOREIGN KEY (grade_id) REFERENCES edu_grade (id)');
        $this->addSql('ALTER TABLE edu_teacher ADD CONSTRAINT FK_89A031C7217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE edu_teacher ADD CONSTRAINT FK_89A031C7C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE edu_teacher ADD CONSTRAINT FK_89A031C7AE80F5DF FOREIGN KEY (department_id) REFERENCES edu_department (id)');
        $this->addSql('ALTER TABLE edu_teaching ADD CONSTRAINT FK_EB30486D41807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE edu_teaching ADD CONSTRAINT FK_EB30486DFE54D947 FOREIGN KEY (group_id) REFERENCES edu_group (id)');
        $this->addSql('ALTER TABLE edu_teaching ADD CONSTRAINT FK_EB30486D23EDC87 FOREIGN KEY (subject_id) REFERENCES edu_subject (id)');
        $this->addSql('ALTER TABLE edu_training ADD CONSTRAINT FK_2692AD50C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD285A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD28532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE manager ADD CONSTRAINT FK_FA2425B932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE manager ADD CONSTRAINT FK_FA2425B9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649AA9E0B02 FOREIGN KEY (default_organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE workcenter ADD CONSTRAINT FK_E2337C97979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE workcenter ADD CONSTRAINT FK_E2337C97783E3463 FOREIGN KEY (manager_id) REFERENCES person (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE workcenter DROP FOREIGN KEY FK_E2337C97979B1AD6');
        $this->addSql('ALTER TABLE edu_department DROP FOREIGN KEY FK_6EB77EB1C54F3401');
        $this->addSql('ALTER TABLE edu_teacher DROP FOREIGN KEY FK_89A031C7C54F3401');
        $this->addSql('ALTER TABLE edu_training DROP FOREIGN KEY FK_2692AD50C54F3401');
        $this->addSql('ALTER TABLE edu_teacher DROP FOREIGN KEY FK_89A031C7AE80F5DF');
        $this->addSql('ALTER TABLE edu_group DROP FOREIGN KEY FK_4C368872FE19A1A8');
        $this->addSql('ALTER TABLE edu_subject DROP FOREIGN KEY FK_C298A968FE19A1A8');
        $this->addSql('ALTER TABLE student_enrollment DROP FOREIGN KEY FK_36033A00FE54D947');
        $this->addSql('ALTER TABLE edu_teaching DROP FOREIGN KEY FK_EB30486DFE54D947');
        $this->addSql('ALTER TABLE edu_learning_outcome DROP FOREIGN KEY FK_301888C623EDC87');
        $this->addSql('ALTER TABLE edu_teaching DROP FOREIGN KEY FK_EB30486D23EDC87');
        $this->addSql('ALTER TABLE edu_academic_year DROP FOREIGN KEY FK_CFBE31D0474870EE');
        $this->addSql('ALTER TABLE edu_academic_year DROP FOREIGN KEY FK_CFBE31D0FD5CC44A');
        $this->addSql('ALTER TABLE edu_department DROP FOREIGN KEY FK_6EB77EB1F41A619E');
        $this->addSql('ALTER TABLE edu_group DROP FOREIGN KEY FK_4C368872208F64F1');
        $this->addSql('ALTER TABLE edu_teaching DROP FOREIGN KEY FK_EB30486D41807E1D');
        $this->addSql('ALTER TABLE edu_competency DROP FOREIGN KEY FK_237FAB0BBEFD98D1');
        $this->addSql('ALTER TABLE edu_grade DROP FOREIGN KEY FK_78AC6283BEFD98D1');
        $this->addSql('ALTER TABLE edu_academic_year DROP FOREIGN KEY FK_CFBE31D032C8A3DE');
        $this->addSql('ALTER TABLE membership DROP FOREIGN KEY FK_86FFD28532C8A3DE');
        $this->addSql('ALTER TABLE manager DROP FOREIGN KEY FK_FA2425B932C8A3DE');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649AA9E0B02');
        $this->addSql('ALTER TABLE company DROP FOREIGN KEY FK_4FBF094F783E3463');
        $this->addSql('ALTER TABLE student_enrollment DROP FOREIGN KEY FK_36033A00217BBB47');
        $this->addSql('ALTER TABLE edu_teacher DROP FOREIGN KEY FK_89A031C7217BBB47');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649217BBB47');
        $this->addSql('ALTER TABLE workcenter DROP FOREIGN KEY FK_E2337C97783E3463');
        $this->addSql('ALTER TABLE membership DROP FOREIGN KEY FK_86FFD285A76ED395');
        $this->addSql('ALTER TABLE manager DROP FOREIGN KEY FK_FA2425B9A76ED395');
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP TABLE edu_academic_year');
        $this->addSql('DROP TABLE edu_competency');
        $this->addSql('DROP TABLE edu_department');
        $this->addSql('DROP TABLE edu_grade');
        $this->addSql('DROP TABLE edu_group');
        $this->addSql('DROP TABLE edu_learning_outcome');
        $this->addSql('DROP TABLE student_enrollment');
        $this->addSql('DROP TABLE edu_subject');
        $this->addSql('DROP TABLE edu_teacher');
        $this->addSql('DROP TABLE edu_teaching');
        $this->addSql('DROP TABLE edu_training');
        $this->addSql('DROP TABLE membership');
        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP TABLE manager');
        $this->addSql('DROP TABLE person');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE workcenter');
    }
}
