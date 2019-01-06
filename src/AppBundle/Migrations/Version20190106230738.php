<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190106230738 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_tracking MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE wlt_tracking DROP FOREIGN KEY FK_EEDEBCDB862E876A');
        $this->addSql('ALTER TABLE wlt_tracking DROP FOREIGN KEY FK_EEDEBCDBA23B8704');
        $this->addSql('DROP INDEX UNIQ_EEDEBCDBA23B8704862E876A ON wlt_tracking');
        $this->addSql('ALTER TABLE wlt_tracking DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wlt_tracking DROP id');
        $this->addSql('ALTER TABLE wlt_tracking ADD CONSTRAINT FK_EEDEBCDB862E876A FOREIGN KEY (activity_realization_id) REFERENCES wlt_activity_realization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_tracking ADD CONSTRAINT FK_EEDEBCDBA23B8704 FOREIGN KEY (work_day_id) REFERENCES wlt_work_day (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_tracking ADD PRIMARY KEY (work_day_id, activity_realization_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_tracking DROP FOREIGN KEY FK_EEDEBCDBA23B8704');
        $this->addSql('ALTER TABLE wlt_tracking DROP FOREIGN KEY FK_EEDEBCDB862E876A');
        $this->addSql('ALTER TABLE wlt_tracking DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wlt_tracking ADD id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE wlt_tracking ADD CONSTRAINT FK_EEDEBCDBA23B8704 FOREIGN KEY (work_day_id) REFERENCES wlt_work_day (id)');
        $this->addSql('ALTER TABLE wlt_tracking ADD CONSTRAINT FK_EEDEBCDB862E876A FOREIGN KEY (activity_realization_id) REFERENCES wlt_activity_realization (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EEDEBCDBA23B8704862E876A ON wlt_tracking (work_day_id, activity_realization_id)');
        $this->addSql('ALTER TABLE wlt_tracking ADD PRIMARY KEY (id)');
    }
}
