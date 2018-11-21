<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181121113253 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edu_teaching DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE edu_teaching ADD id INT AUTO_INCREMENT NOT NULL PRIMARY KEY');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EB30486D41807E1DFE54D94723EDC87 ON edu_teaching (teacher_id, group_id, subject_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edu_teaching MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX UNIQ_EB30486D41807E1DFE54D94723EDC87 ON edu_teaching');
        $this->addSql('ALTER TABLE edu_teaching DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE edu_teaching DROP id');
        $this->addSql('ALTER TABLE edu_teaching ADD PRIMARY KEY (teacher_id, group_id, subject_id)');
    }
}
