<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181208221416 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edu_academic_year ADD start_date DATE DEFAULT NULL, ADD end_date DATE DEFAULT NULL');
        $this->addSql('UPDATE edu_academic_year SET start_date = CONCAT(SUBSTRING(edu_academic_year.description, 1, 4),"/09/01"), end_date = CONCAT(SUBSTRING(edu_academic_year.description, 1, 4)+1,"/08/31")');
        $this->addSql('ALTER TABLE edu_academic_year CHANGE start_date start_date DATE NOT NULL, CHANGE end_date end_date DATE NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edu_academic_year DROP start_date, DROP end_date');
    }
}
