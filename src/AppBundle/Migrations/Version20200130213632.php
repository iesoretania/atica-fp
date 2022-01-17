<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200130213632 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_activity_realization_grade ADD project_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wlt_activity_realization_grade ADD CONSTRAINT FK_CD1FF777166D1F9C FOREIGN KEY (project_id) REFERENCES wlt_project (id)');
        $this->addSql('CREATE INDEX IDX_CD1FF777166D1F9C ON wlt_activity_realization_grade (project_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $$this->throwIrreversibleMigrationException("Sorry! Cannot downgrade to 2.0.x");
    }
}
