<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200604183226 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wpt_shift ADD activity_summary_report_template_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wpt_shift ADD CONSTRAINT FK_584DA9607791D10 FOREIGN KEY (activity_summary_report_template_id) REFERENCES edu_report_template (id)');
        $this->addSql('CREATE INDEX IDX_584DA9607791D10 ON wpt_shift (activity_summary_report_template_id)');
        $this->addSql('ALTER TABLE wpt_shift_audit ADD activity_summary_report_template_id INT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wpt_shift DROP FOREIGN KEY FK_584DA9607791D10');
        $this->addSql('DROP INDEX IDX_584DA9607791D10 ON wpt_shift');
        $this->addSql('ALTER TABLE wpt_shift DROP activity_summary_report_template_id');
        $this->addSql('ALTER TABLE wpt_shift_audit DROP activity_summary_report_template_id');
    }
}
