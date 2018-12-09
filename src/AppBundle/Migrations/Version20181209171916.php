<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181209171916 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE wlt_agreement (id INT AUTO_INCREMENT NOT NULL, workcenter_id INT NOT NULL, student_enrollment_id INT NOT NULL, work_tutor_id INT NOT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, student_poll_submitted TINYINT(1) NOT NULL, default_start_time1 VARCHAR(5) DEFAULT NULL, default_end_time1 VARCHAR(5) DEFAULT NULL, default_start_time2 VARCHAR(5) DEFAULT NULL, default_end_time2 VARCHAR(5) DEFAULT NULL, INDEX IDX_2B23AFE9A2473C4B (workcenter_id), INDEX IDX_2B23AFE9DAE14AC5 (student_enrollment_id), INDEX IDX_2B23AFE9F53AEEAD (work_tutor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wlt_agreement ADD CONSTRAINT FK_2B23AFE9A2473C4B FOREIGN KEY (workcenter_id) REFERENCES workcenter (id)');
        $this->addSql('ALTER TABLE wlt_agreement ADD CONSTRAINT FK_2B23AFE9DAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id)');
        $this->addSql('ALTER TABLE wlt_agreement ADD CONSTRAINT FK_2B23AFE9F53AEEAD FOREIGN KEY (work_tutor_id) REFERENCES person (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE wlt_agreement');
    }
}
