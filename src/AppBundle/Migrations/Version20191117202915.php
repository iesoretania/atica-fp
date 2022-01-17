<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20191117202915 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey ADD academic_year_id INT NOT NULL');
        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey ADD CONSTRAINT FK_CBD11B75C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('CREATE INDEX IDX_CBD11B75C54F3401 ON wlt_educational_tutor_answered_survey (academic_year_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey DROP FOREIGN KEY FK_CBD11B75C54F3401');
        $this->addSql('DROP INDEX IDX_CBD11B75C54F3401 ON wlt_educational_tutor_answered_survey');
        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey DROP academic_year_id');
    }
}
