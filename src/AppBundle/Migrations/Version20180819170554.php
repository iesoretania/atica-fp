<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180819170554 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ict_ticket (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, created_by_id INT NOT NULL, assignee_id INT DEFAULT NULL, closed_by_id INT DEFAULT NULL, duplicates_id INT DEFAULT NULL, description LONGTEXT NOT NULL, created_on DATETIME NOT NULL, last_updated_on DATETIME NOT NULL, closed_on DATETIME DEFAULT NULL, due_on DATE DEFAULT NULL, priority INT NOT NULL, notes LONGTEXT DEFAULT NULL, INDEX IDX_7073F09932C8A3DE (organization_id), INDEX IDX_7073F099B03A8386 (created_by_id), INDEX IDX_7073F09959EC7D60 (assignee_id), INDEX IDX_7073F099E1FA7797 (closed_by_id), INDEX IDX_7073F099EAA0C5EF (duplicates_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE location (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_5E9E89CB32C8A3DE (organization_id), INDEX IDX_5E9E89CB727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ict_ticket ADD CONSTRAINT FK_7073F09932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE ict_ticket ADD CONSTRAINT FK_7073F099B03A8386 FOREIGN KEY (created_by_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE ict_ticket ADD CONSTRAINT FK_7073F09959EC7D60 FOREIGN KEY (assignee_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE ict_ticket ADD CONSTRAINT FK_7073F099E1FA7797 FOREIGN KEY (closed_by_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE ict_ticket ADD CONSTRAINT FK_7073F099EAA0C5EF FOREIGN KEY (duplicates_id) REFERENCES ict_ticket (id)');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB727ACA70 FOREIGN KEY (parent_id) REFERENCES location (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ict_ticket DROP FOREIGN KEY FK_7073F099EAA0C5EF');
        $this->addSql('ALTER TABLE location DROP FOREIGN KEY FK_5E9E89CB727ACA70');
        $this->addSql('DROP TABLE ict_ticket');
        $this->addSql('DROP TABLE location');
    }
}
