<?php

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200220223755 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_project ADD weekly_activity_report_template_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wlt_project ADD CONSTRAINT FK_E4D36E413BBF8EEC FOREIGN KEY (weekly_activity_report_template_id) REFERENCES edu_report_template (id)');
        $this->addSql('CREATE INDEX IDX_E4D36E413BBF8EEC ON wlt_project (weekly_activity_report_template_id)');
        $this->addSql('ALTER TABLE wlt_project_audit ADD weekly_activity_report_template_id INT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_project DROP FOREIGN KEY FK_E4D36E413BBF8EEC');
        $this->addSql('DROP INDEX IDX_E4D36E413BBF8EEC ON wlt_project');
        $this->addSql('ALTER TABLE wlt_project DROP weekly_activity_report_template_id');
        $this->addSql('ALTER TABLE wlt_project_audit DROP weekly_activity_report_template_id');
    }
}
