<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200304210829 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edu_academic_year ADD default_portrait_template_id INT DEFAULT NULL, ADD default_landscape_template_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE edu_academic_year ADD CONSTRAINT FK_CFBE31D0FD003EB1 FOREIGN KEY (default_portrait_template_id) REFERENCES edu_report_template (id)');
        $this->addSql('ALTER TABLE edu_academic_year ADD CONSTRAINT FK_CFBE31D07179DA55 FOREIGN KEY (default_landscape_template_id) REFERENCES edu_report_template (id)');
        $this->addSql('CREATE INDEX IDX_CFBE31D0FD003EB1 ON edu_academic_year (default_portrait_template_id)');
        $this->addSql('CREATE INDEX IDX_CFBE31D07179DA55 ON edu_academic_year (default_landscape_template_id)');
        $this->addSql('ALTER TABLE edu_academic_year_audit ADD default_portrait_template_id INT DEFAULT NULL, ADD default_landscape_template_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE organization DROP header, DROP footer');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edu_academic_year DROP FOREIGN KEY FK_CFBE31D0FD003EB1');
        $this->addSql('ALTER TABLE edu_academic_year DROP FOREIGN KEY FK_CFBE31D07179DA55');
        $this->addSql('DROP INDEX IDX_CFBE31D0FD003EB1 ON edu_academic_year');
        $this->addSql('DROP INDEX IDX_CFBE31D07179DA55 ON edu_academic_year');
        $this->addSql('ALTER TABLE edu_academic_year DROP default_portrait_template_id, DROP default_landscape_template_id');
        $this->addSql('ALTER TABLE edu_academic_year_audit DROP default_portrait_template_id, DROP default_landscape_template_id');
        $this->addSql('ALTER TABLE organization ADD header LONGTEXT DEFAULT NULL COLLATE utf8mb4_spanish_ci, ADD footer VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_spanish_ci');
    }
}
