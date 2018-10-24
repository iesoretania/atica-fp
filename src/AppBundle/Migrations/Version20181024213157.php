<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181024213157 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ict_mac_address (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, person_id INT NOT NULL, address VARCHAR(17) NOT NULL, description LONGTEXT NOT NULL, created_on DATE NOT NULL, registered_on DATE DEFAULT NULL, un_registered_on DATE DEFAULT NULL, INDEX IDX_FB2E707332C8A3DE (organization_id), INDEX IDX_FB2E7073217BBB47 (person_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ict_mac_address ADD CONSTRAINT FK_FB2E707332C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE ict_mac_address ADD CONSTRAINT FK_FB2E7073217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE ict_mac_address');
    }
}
