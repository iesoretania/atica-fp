<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181114222134 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE company (id INT AUTO_INCREMENT NOT NULL, manager_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(255) NOT NULL, zip_code VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, fax_number VARCHAR(255) DEFAULT NULL, email_address VARCHAR(255) DEFAULT NULL, web_site VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_4FBF094F77153098 (code), INDEX IDX_4FBF094F783E3463 (manager_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workcenter (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, manager_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(255) NOT NULL, zip_code VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, fax_number VARCHAR(255) DEFAULT NULL, email_address VARCHAR(255) DEFAULT NULL, INDEX IDX_E2337C97979B1AD6 (company_id), INDEX IDX_E2337C97783E3463 (manager_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE company ADD CONSTRAINT FK_4FBF094F783E3463 FOREIGN KEY (manager_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE workcenter ADD CONSTRAINT FK_E2337C97979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE workcenter ADD CONSTRAINT FK_E2337C97783E3463 FOREIGN KEY (manager_id) REFERENCES person (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE workcenter DROP FOREIGN KEY FK_E2337C97979B1AD6');
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP TABLE workcenter');
    }
}
