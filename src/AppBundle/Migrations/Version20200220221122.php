<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200220221122 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_project ADD attendance_report_template_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wlt_project ADD CONSTRAINT FK_E4D36E4113E6472B FOREIGN KEY (attendance_report_template_id) REFERENCES edu_report_template (id)');
        $this->addSql('CREATE INDEX IDX_E4D36E4113E6472B ON wlt_project (attendance_report_template_id)');
        $this->addSql('ALTER TABLE wlt_project_audit ADD attendance_report_template_id INT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_project DROP FOREIGN KEY FK_E4D36E4113E6472B');
        $this->addSql('DROP INDEX IDX_E4D36E4113E6472B ON wlt_project');
        $this->addSql('ALTER TABLE wlt_project DROP attendance_report_template_id');
        $this->addSql('ALTER TABLE wlt_project_audit DROP attendance_report_template_id');
    }
}
