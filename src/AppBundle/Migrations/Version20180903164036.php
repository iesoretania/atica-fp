<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180903164036 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ict_ticket ADD element_id INT NOT NULL');
        $this->addSql('ALTER TABLE ict_ticket ADD CONSTRAINT FK_7073F0991F1F2A24 FOREIGN KEY (element_id) REFERENCES ict_element (id)');
        $this->addSql('CREATE INDEX IDX_7073F0991F1F2A24 ON ict_ticket (element_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ict_ticket DROP FOREIGN KEY FK_7073F0991F1F2A24');
        $this->addSql('DROP INDEX IDX_7073F0991F1F2A24 ON ict_ticket');
        $this->addSql('ALTER TABLE ict_ticket DROP element_id');
    }
}
