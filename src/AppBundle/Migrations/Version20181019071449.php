<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181019071449 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ict_ticket CHANGE priority priority_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ict_ticket ADD CONSTRAINT FK_7073F099497B19F9 FOREIGN KEY (priority_id) REFERENCES ict_priority (id)');
        $this->addSql('CREATE INDEX IDX_7073F099497B19F9 ON ict_ticket (priority_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ict_ticket DROP FOREIGN KEY FK_7073F099497B19F9');
        $this->addSql('DROP INDEX IDX_7073F099497B19F9 ON ict_ticket');
        $this->addSql('ALTER TABLE ict_ticket CHANGE priority_id priority INT DEFAULT NULL');
    }
}
