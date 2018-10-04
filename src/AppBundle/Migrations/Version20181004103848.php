<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181004103848 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ict_element ADD reference VARCHAR(255) DEFAULT NULL, ADD detail LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX reference_idx ON ict_element (organization_id, reference)');
        $this->addSql('ALTER TABLE ict_location RENAME INDEX IDX_5E9E89CB32C8A3DE TO IDX_6EED9D2832C8A3DE');
        $this->addSql('ALTER TABLE ict_location RENAME INDEX IDX_5E9E89CB727ACA70 TO IDX_6EED9D28727ACA70');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX reference_idx ON ict_element');
        $this->addSql('ALTER TABLE ict_element DROP reference, DROP detail');
        $this->addSql('ALTER TABLE ict_location RENAME INDEX IDX_6EED9D2832C8A3DE TO IDX_5E9E89CB32C8A3DE');
        $this->addSql('ALTER TABLE ict_location RENAME INDEX IDX_6EED9D28727ACA70 TO IDX_5E9E89CB727ACA70');
    }
}
