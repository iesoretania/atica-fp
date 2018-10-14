<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181014210206 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ict_priority ADD organization_id INT NOT NULL');
        $this->addSql('ALTER TABLE ict_priority ADD CONSTRAINT FK_52D5C8C432C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('CREATE INDEX IDX_52D5C8C432C8A3DE ON ict_priority (organization_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ict_priority DROP FOREIGN KEY FK_52D5C8C432C8A3DE');
        $this->addSql('DROP INDEX IDX_52D5C8C432C8A3DE ON ict_priority');
        $this->addSql('ALTER TABLE ict_priority DROP organization_id');
    }
}
