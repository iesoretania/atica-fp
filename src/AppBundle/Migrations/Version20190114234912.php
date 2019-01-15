<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190114234912 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_meeting ADD created_by_id INT DEFAULT NULL');
        $this->addSql('UPDATE wlt_meeting SET created_by_id = (SELECT edu_teacher.id FROM edu_teacher WHERE academic_year_id = wlt_meeting.academic_year_id LIMIT 1)');
        $this->addSql('ALTER TABLE wlt_meeting CHANGE created_by_id created_by_id INT NOT NULL');
        $this->addSql('ALTER TABLE wlt_meeting ADD CONSTRAINT FK_3E755F96B03A8386 FOREIGN KEY (created_by_id) REFERENCES edu_teacher (id)');
        $this->addSql('CREATE INDEX IDX_3E755F96B03A8386 ON wlt_meeting (created_by_id)');
        $this->addSql('ALTER TABLE wlt_meeting CHANGE date date_time DATETIME NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_meeting CHANGE date_time date DATE NOT NULL');
        $this->addSql('ALTER TABLE wlt_meeting DROP FOREIGN KEY FK_3E755F96B03A8386');
        $this->addSql('DROP INDEX IDX_3E755F96B03A8386 ON wlt_meeting');
        $this->addSql('ALTER TABLE wlt_meeting DROP created_by_id');
    }
}
