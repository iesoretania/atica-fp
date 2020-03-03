<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200303222749 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE wpt_report (agreement_id INT NOT NULL, work_activities LONGTEXT NOT NULL, professional_competence INT NOT NULL, organizational_competence INT NOT NULL, relational_competence INT NOT NULL, contingency_response INT NOT NULL, other_description1 VARCHAR(255) DEFAULT NULL, other1 INT DEFAULT NULL, other_description2 VARCHAR(255) DEFAULT NULL, other2 INT DEFAULT NULL, proposed_changes LONGTEXT DEFAULT NULL, sign_date DATE NOT NULL, PRIMARY KEY(agreement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wpt_report ADD CONSTRAINT FK_8FD6E55124890B2B FOREIGN KEY (agreement_id) REFERENCES wpt_agreement (id)');
        $this->addSql('ALTER TABLE wpt_shift ADD final_report_template_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wpt_shift ADD CONSTRAINT FK_584DA96010BDE131 FOREIGN KEY (final_report_template_id) REFERENCES edu_report_template (id)');
        $this->addSql('CREATE INDEX IDX_584DA96010BDE131 ON wpt_shift (final_report_template_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE wpt_report');
        $this->addSql('ALTER TABLE wpt_shift DROP FOREIGN KEY FK_584DA96010BDE131');
        $this->addSql('DROP INDEX IDX_584DA96010BDE131 ON wpt_shift');
        $this->addSql('ALTER TABLE wpt_shift DROP final_report_template_id');
    }
}
