<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181209221700 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE wlt_agreement_activity_realization (agreement_id INT NOT NULL, activity_realization_id INT NOT NULL, INDEX IDX_BD86F07824890B2B (agreement_id), INDEX IDX_BD86F078862E876A (activity_realization_id), PRIMARY KEY(agreement_id, activity_realization_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD CONSTRAINT FK_BD86F07824890B2B FOREIGN KEY (agreement_id) REFERENCES wlt_agreement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD CONSTRAINT FK_BD86F078862E876A FOREIGN KEY (activity_realization_id) REFERENCES wlt_activity_realization (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE wlt_agreement_activity_realization');
    }
}
