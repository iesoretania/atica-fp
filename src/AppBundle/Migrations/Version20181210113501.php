<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181210113501 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE wlt_tracking (id INT AUTO_INCREMENT NOT NULL, work_day_id INT NOT NULL, activity_realization_id INT NOT NULL, INDEX IDX_EEDEBCDBA23B8704 (work_day_id), INDEX IDX_EEDEBCDB862E876A (activity_realization_id), UNIQUE INDEX UNIQ_EEDEBCDBA23B8704862E876A (work_day_id, activity_realization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_work_day (id INT AUTO_INCREMENT NOT NULL, agreement_id INT NOT NULL, hours INT NOT NULL, date DATE NOT NULL, notes LONGTEXT DEFAULT NULL, locked TINYINT(1) NOT NULL, absence TINYINT(1) NOT NULL, start_time1 VARCHAR(5) DEFAULT NULL, end_time1 VARCHAR(5) DEFAULT NULL, start_time2 VARCHAR(5) DEFAULT NULL, end_time2 VARCHAR(5) DEFAULT NULL, INDEX IDX_D96CA0CB24890B2B (agreement_id), UNIQUE INDEX UNIQ_D96CA0CB24890B2BAA9E377A (agreement_id, date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wlt_tracking ADD CONSTRAINT FK_EEDEBCDBA23B8704 FOREIGN KEY (work_day_id) REFERENCES wlt_work_day (id)');
        $this->addSql('ALTER TABLE wlt_tracking ADD CONSTRAINT FK_EEDEBCDB862E876A FOREIGN KEY (activity_realization_id) REFERENCES wlt_activity_realization (id)');
        $this->addSql('ALTER TABLE wlt_work_day ADD CONSTRAINT FK_D96CA0CB24890B2B FOREIGN KEY (agreement_id) REFERENCES wlt_agreement (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EAD6D79D23EDC8777153098 ON wlt_activity (subject_id, code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BA14088981C0609677153098 ON wlt_activity_realization (activity_id, code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2B23AFE9DAE14AC5A2473C4B ON wlt_agreement (student_enrollment_id, workcenter_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_tracking DROP FOREIGN KEY FK_EEDEBCDBA23B8704');
        $this->addSql('DROP TABLE wlt_tracking');
        $this->addSql('DROP TABLE wlt_work_day');
        $this->addSql('DROP INDEX UNIQ_EAD6D79D23EDC8777153098 ON wlt_activity');
        $this->addSql('DROP INDEX UNIQ_BA14088981C0609677153098 ON wlt_activity_realization');
        $this->addSql('DROP INDEX UNIQ_2B23AFE9DAE14AC5A2473C4B ON wlt_agreement');
    }
}
