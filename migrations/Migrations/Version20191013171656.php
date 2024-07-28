<?php

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20191013171656 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE workcenter DROP FOREIGN KEY FK_E2337C97C54F3401');
        $this->addSql('DROP INDEX IDX_E2337C97C54F3401 ON workcenter');
        $this->addSql('ALTER TABLE workcenter DROP academic_year_id');
        $this->addSql('ALTER TABLE workcenter_audit DROP academic_year_id');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE workcenter ADD academic_year_id INT NOT NULL');
        $this->addSql('ALTER TABLE workcenter ADD CONSTRAINT FK_E2337C97C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('CREATE INDEX IDX_E2337C97C54F3401 ON workcenter (academic_year_id)');
        $this->addSql('ALTER TABLE workcenter_audit ADD academic_year_id INT DEFAULT NULL');
    }
}
