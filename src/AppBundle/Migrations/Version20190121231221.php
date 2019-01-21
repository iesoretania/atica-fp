<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190121231221 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE company_audit (id INT NOT NULL, rev INT NOT NULL, manager_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, code VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, zip_code VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, fax_number VARCHAR(255) DEFAULT NULL, email_address VARCHAR(255) DEFAULT NULL, web_site VARCHAR(255) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_3af728e1cf16bdb0f83bb90e3b1af48a_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workcenter_audit (id INT NOT NULL, rev INT NOT NULL, company_id INT DEFAULT NULL, academic_year_id INT DEFAULT NULL, manager_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, zip_code VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, fax_number VARCHAR(255) DEFAULT NULL, email_address VARCHAR(255) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_54a4a65186f56b9de79ff6f4b726b582_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_academic_year_audit (id INT NOT NULL, rev INT NOT NULL, organization_id INT DEFAULT NULL, principal_id INT DEFAULT NULL, financial_manager_id INT DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_32b80cc3e6e41d58d2de465d160f9958_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_work_day_audit (id INT NOT NULL, rev INT NOT NULL, agreement_id INT DEFAULT NULL, hours INT DEFAULT NULL, date DATE DEFAULT NULL, notes LONGTEXT DEFAULT NULL, locked TINYINT(1) DEFAULT NULL, absence INT DEFAULT NULL, start_time1 VARCHAR(5) DEFAULT NULL, end_time1 VARCHAR(5) DEFAULT NULL, start_time2 VARCHAR(5) DEFAULT NULL, end_time2 VARCHAR(5) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_a182ece7d34180bb9b41759ce4dafebc_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_learning_program_audit (id INT NOT NULL, rev INT NOT NULL, company_id INT DEFAULT NULL, training_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_3e4a8a47037e8657861e40d29d7511a6_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_meeting_audit (id INT NOT NULL, rev INT NOT NULL, academic_year_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, date_time DATETIME DEFAULT NULL, detail LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_4f5b29d8966971ed79a052aacd98c68f_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_visit_audit (id INT NOT NULL, rev INT NOT NULL, teacher_id INT DEFAULT NULL, workcenter_id INT DEFAULT NULL, date_time DATETIME DEFAULT NULL, detail LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_f8bf02d8fb496c8df3fcc8ad9bc828b7_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_audit (id INT NOT NULL, rev INT NOT NULL, default_organization_id INT DEFAULT NULL, login_username VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, force_password_change TINYINT(1) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, global_administrator TINYINT(1) DEFAULT NULL, email_address VARCHAR(255) DEFAULT NULL, token VARCHAR(255) DEFAULT NULL, token_type VARCHAR(255) DEFAULT NULL, token_expiration DATETIME DEFAULT NULL, last_access DATETIME DEFAULT NULL, blocked_until DATETIME DEFAULT NULL, external_check TINYINT(1) DEFAULT NULL, allow_external_check TINYINT(1) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_e06395edc291d0719bee26fd39a32e8a_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role_audit (id INT NOT NULL, rev INT NOT NULL, person_id INT DEFAULT NULL, organization_id INT DEFAULT NULL, role VARCHAR(20) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_92317bf7adb4788531df0b1cb910b5fc_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE person_audit (id INT NOT NULL, rev INT NOT NULL, user_id INT DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, internal_code VARCHAR(255) DEFAULT NULL, unique_identifier VARCHAR(255) DEFAULT NULL, gender INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_907be00c9c366335b3359c1e8e2f6227_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_agreement_activity_realization_audit (id INT NOT NULL, rev INT NOT NULL, agreement_id INT DEFAULT NULL, activity_realization_id INT DEFAULT NULL, grade_id INT DEFAULT NULL, graded_by_id INT DEFAULT NULL, graded_on DATE DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_3aa810fb5b23d95bfecc473791749abf_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE revisions (id INT AUTO_INCREMENT NOT NULL, timestamp DATETIME NOT NULL, username VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE company_audit');
        $this->addSql('DROP TABLE workcenter_audit');
        $this->addSql('DROP TABLE edu_academic_year_audit');
        $this->addSql('DROP TABLE wlt_work_day_audit');
        $this->addSql('DROP TABLE wlt_learning_program_audit');
        $this->addSql('DROP TABLE wlt_meeting_audit');
        $this->addSql('DROP TABLE wlt_visit_audit');
        $this->addSql('DROP TABLE user_audit');
        $this->addSql('DROP TABLE role_audit');
        $this->addSql('DROP TABLE person_audit');
        $this->addSql('DROP TABLE wlt_agreement_activity_realization_audit');
        $this->addSql('DROP TABLE revisions');
    }
}
