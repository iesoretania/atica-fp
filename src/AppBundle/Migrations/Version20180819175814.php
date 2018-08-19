<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180819175814 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ict_used_consumable (work_order_id INT NOT NULL, consumable_id INT NOT NULL, quantity INT NOT NULL, INDEX IDX_E461E0DC582AE764 (work_order_id), INDEX IDX_E461E0DCA94ADB61 (consumable_id), PRIMARY KEY(work_order_id, consumable_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ict_work_order (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, person_id INT NOT NULL, ticket_id INT DEFAULT NULL, description LONGTEXT NOT NULL, started_on DATETIME NOT NULL, finished_on DATETIME DEFAULT NULL, INDEX IDX_B25BAC9632C8A3DE (organization_id), INDEX IDX_B25BAC96217BBB47 (person_id), INDEX IDX_B25BAC96700047D2 (ticket_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ict_consumable (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, location_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, quantity INT NOT NULL, minimum INT DEFAULT NULL, alert TINYINT(1) NOT NULL, INDEX IDX_2BFCB4B432C8A3DE (organization_id), INDEX IDX_2BFCB4B464D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ict_used_consumable ADD CONSTRAINT FK_E461E0DC582AE764 FOREIGN KEY (work_order_id) REFERENCES ict_work_order (id)');
        $this->addSql('ALTER TABLE ict_used_consumable ADD CONSTRAINT FK_E461E0DCA94ADB61 FOREIGN KEY (consumable_id) REFERENCES ict_consumable (id)');
        $this->addSql('ALTER TABLE ict_work_order ADD CONSTRAINT FK_B25BAC9632C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE ict_work_order ADD CONSTRAINT FK_B25BAC96217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE ict_work_order ADD CONSTRAINT FK_B25BAC96700047D2 FOREIGN KEY (ticket_id) REFERENCES ict_ticket (id)');
        $this->addSql('ALTER TABLE ict_consumable ADD CONSTRAINT FK_2BFCB4B432C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE ict_consumable ADD CONSTRAINT FK_2BFCB4B464D218E FOREIGN KEY (location_id) REFERENCES location (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ict_used_consumable DROP FOREIGN KEY FK_E461E0DC582AE764');
        $this->addSql('ALTER TABLE ict_used_consumable DROP FOREIGN KEY FK_E461E0DCA94ADB61');
        $this->addSql('DROP TABLE ict_used_consumable');
        $this->addSql('DROP TABLE ict_work_order');
        $this->addSql('DROP TABLE ict_consumable');
    }
}
