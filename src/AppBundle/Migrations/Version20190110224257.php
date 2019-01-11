<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190110224257 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE wlt_visit (id INT AUTO_INCREMENT NOT NULL, teacher_id INT NOT NULL, workcenter_id INT NOT NULL, date_time DATETIME NOT NULL, detail LONGTEXT DEFAULT NULL, INDEX IDX_952C5F7841807E1D (teacher_id), INDEX IDX_952C5F78A2473C4B (workcenter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wlt_visit ADD CONSTRAINT FK_952C5F7841807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE wlt_visit ADD CONSTRAINT FK_952C5F78A2473C4B FOREIGN KEY (workcenter_id) REFERENCES workcenter (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE wlt_visit');
    }
}
