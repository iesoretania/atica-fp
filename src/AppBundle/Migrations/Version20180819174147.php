<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180819174147 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ict_element (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, parent_id INT DEFAULT NULL, location_id INT DEFAULT NULL, template_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, serial_number VARCHAR(255) DEFAULT NULL, listed_on DATE DEFAULT NULL, delisted_on DATE DEFAULT NULL, INDEX IDX_87AB54D632C8A3DE (organization_id), INDEX IDX_87AB54D6727ACA70 (parent_id), INDEX IDX_87AB54D664D218E (location_id), INDEX IDX_87AB54D65DA0FB8 (template_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ict_element_template (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_49C83C9832C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ict_element ADD CONSTRAINT FK_87AB54D632C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE ict_element ADD CONSTRAINT FK_87AB54D6727ACA70 FOREIGN KEY (parent_id) REFERENCES ict_element (id)');
        $this->addSql('ALTER TABLE ict_element ADD CONSTRAINT FK_87AB54D664D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE ict_element ADD CONSTRAINT FK_87AB54D65DA0FB8 FOREIGN KEY (template_id) REFERENCES ict_element_template (id)');
        $this->addSql('ALTER TABLE ict_element_template ADD CONSTRAINT FK_49C83C9832C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ict_element DROP FOREIGN KEY FK_87AB54D6727ACA70');
        $this->addSql('ALTER TABLE ict_element DROP FOREIGN KEY FK_87AB54D65DA0FB8');
        $this->addSql('DROP TABLE ict_element');
        $this->addSql('DROP TABLE ict_element_template');
    }
}
