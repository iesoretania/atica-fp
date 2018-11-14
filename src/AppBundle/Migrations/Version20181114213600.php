<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181114213600 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edu_academic_year ADD principal_id INT DEFAULT NULL, ADD financial_manager_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE edu_academic_year ADD CONSTRAINT FK_CFBE31D0474870EE FOREIGN KEY (principal_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE edu_academic_year ADD CONSTRAINT FK_CFBE31D0FD5CC44A FOREIGN KEY (financial_manager_id) REFERENCES edu_teacher (id)');
        $this->addSql('CREATE INDEX IDX_CFBE31D0474870EE ON edu_academic_year (principal_id)');
        $this->addSql('CREATE INDEX IDX_CFBE31D0FD5CC44A ON edu_academic_year (financial_manager_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edu_academic_year DROP FOREIGN KEY FK_CFBE31D0474870EE');
        $this->addSql('ALTER TABLE edu_academic_year DROP FOREIGN KEY FK_CFBE31D0FD5CC44A');
        $this->addSql('DROP INDEX IDX_CFBE31D0474870EE ON edu_academic_year');
        $this->addSql('DROP INDEX IDX_CFBE31D0FD5CC44A ON edu_academic_year');
        $this->addSql('ALTER TABLE edu_academic_year DROP principal_id, DROP financial_manager_id');
    }
}
