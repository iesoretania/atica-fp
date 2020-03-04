<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200304220612 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE wpt_shift_audit (id INT NOT NULL, rev INT NOT NULL, subject_id INT DEFAULT NULL, student_survey_id INT DEFAULT NULL, company_survey_id INT DEFAULT NULL, educational_tutor_survey_id INT DEFAULT NULL, attendance_report_template_id INT DEFAULT NULL, final_report_template_id INT DEFAULT NULL, weekly_activity_report_template_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, hours INT DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, quarter INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_71f73a2d4dc70e8d55eaad661689e458_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_activity_audit (id INT NOT NULL, rev INT NOT NULL, shift_id INT DEFAULT NULL, code VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_9c2622768c8baa59674e5941fb6223f9_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_agreement_audit (id INT NOT NULL, rev INT NOT NULL, shift_id INT DEFAULT NULL, workcenter_id INT DEFAULT NULL, student_enrollment_id INT DEFAULT NULL, work_tutor_id INT DEFAULT NULL, educational_tutor_id INT DEFAULT NULL, student_survey_id INT DEFAULT NULL, company_survey_id INT DEFAULT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, sign_date DATE DEFAULT NULL, default_start_time1 VARCHAR(5) DEFAULT NULL, default_end_time1 VARCHAR(5) DEFAULT NULL, default_start_time2 VARCHAR(5) DEFAULT NULL, default_end_time2 VARCHAR(5) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_c44410e99c46f4c316201779be629ca6_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_work_day_audit (id INT NOT NULL, rev INT NOT NULL, agreement_id INT DEFAULT NULL, hours INT DEFAULT NULL, date DATE DEFAULT NULL, notes LONGTEXT DEFAULT NULL, other_activities LONGTEXT DEFAULT NULL, locked TINYINT(1) DEFAULT NULL, absence INT DEFAULT NULL, start_time1 VARCHAR(5) DEFAULT NULL, end_time1 VARCHAR(5) DEFAULT NULL, start_time2 VARCHAR(5) DEFAULT NULL, end_time2 VARCHAR(5) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_5078b049add7547b5901b17a05bf1608_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_report_audit (agreement_id INT NOT NULL, rev INT NOT NULL, work_activities LONGTEXT DEFAULT NULL, professional_competence INT DEFAULT NULL, organizational_competence INT DEFAULT NULL, relational_competence INT DEFAULT NULL, contingency_response INT DEFAULT NULL, other_description1 VARCHAR(255) DEFAULT NULL, other1 INT DEFAULT NULL, other_description2 VARCHAR(255) DEFAULT NULL, other2 INT DEFAULT NULL, proposed_changes LONGTEXT DEFAULT NULL, sign_date DATE DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_fd82d0cd772f0fbe220033a10b2261ae_idx (rev), PRIMARY KEY(agreement_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE wpt_shift_audit');
        $this->addSql('DROP TABLE wpt_activity_audit');
        $this->addSql('DROP TABLE wpt_agreement_audit');
        $this->addSql('DROP TABLE wpt_work_day_audit');
        $this->addSql('DROP TABLE wpt_report_audit');
    }
}
