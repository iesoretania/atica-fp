<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200308211815 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wpt_tracked_work_day DROP FOREIGN KEY FK_E7E67EDADAE14AC5');
        $this->addSql('DROP INDEX UNIQ_E7E67EDADAE14AC5A23B8704 ON wpt_tracked_work_day');
        $this->addSql('DROP INDEX IDX_E7E67EDADAE14AC5 ON wpt_tracked_work_day');
        $this->addSql('ALTER TABLE wpt_tracked_work_day CHANGE student_enrollment_id agreement_enrollment_id INT NOT NULL');
        $this->addSql('ALTER TABLE wpt_tracked_work_day ADD CONSTRAINT FK_E7E67EDAA32BEB28 FOREIGN KEY (agreement_enrollment_id) REFERENCES wpt_agreement_enrollment (id)');
        $this->addSql('CREATE INDEX IDX_E7E67EDAA32BEB28 ON wpt_tracked_work_day (agreement_enrollment_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E7E67EDAA32BEB28A23B8704 ON wpt_tracked_work_day (agreement_enrollment_id, work_day_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wpt_tracked_work_day DROP FOREIGN KEY FK_E7E67EDAA32BEB28');
        $this->addSql('DROP INDEX IDX_E7E67EDAA32BEB28 ON wpt_tracked_work_day');
        $this->addSql('DROP INDEX UNIQ_E7E67EDAA32BEB28A23B8704 ON wpt_tracked_work_day');
        $this->addSql('ALTER TABLE wpt_tracked_work_day CHANGE agreement_enrollment_id student_enrollment_id INT NOT NULL');
        $this->addSql('ALTER TABLE wpt_tracked_work_day ADD CONSTRAINT FK_E7E67EDADAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E7E67EDADAE14AC5A23B8704 ON wpt_tracked_work_day (student_enrollment_id, work_day_id)');
        $this->addSql('CREATE INDEX IDX_E7E67EDADAE14AC5 ON wpt_tracked_work_day (student_enrollment_id)');
    }
}
