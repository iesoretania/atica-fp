<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181023171829 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ict_element DROP FOREIGN KEY FK_87AB54D6727ACA70');
        $this->addSql('DROP INDEX IDX_87AB54D6727ACA70 ON ict_element');
        $this->addSql('ALTER TABLE ict_element DROP parent_id');
        $this->addSql('ALTER TABLE ict_location DROP FOREIGN KEY FK_5E9E89CB727ACA70');
        $this->addSql('DROP INDEX IDX_6EED9D28727ACA70 ON ict_location');
        $this->addSql('ALTER TABLE ict_location ADD hidden TINYINT(1) NOT NULL, DROP parent_id');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ict_element ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ict_element ADD CONSTRAINT FK_87AB54D6727ACA70 FOREIGN KEY (parent_id) REFERENCES ict_element (id)');
        $this->addSql('CREATE INDEX IDX_87AB54D6727ACA70 ON ict_element (parent_id)');
        $this->addSql('ALTER TABLE ict_location ADD parent_id INT DEFAULT NULL, DROP hidden');
        $this->addSql('ALTER TABLE ict_location ADD CONSTRAINT FK_5E9E89CB727ACA70 FOREIGN KEY (parent_id) REFERENCES ict_location (id)');
        $this->addSql('CREATE INDEX IDX_6EED9D28727ACA70 ON ict_location (parent_id)');
    }
}
