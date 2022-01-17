<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190610105704 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edu_training DROP FOREIGN KEY FK_2692AD50F913D1F');
        $this->addSql('DROP INDEX IDX_2692AD50F913D1F ON edu_training');
        $this->addSql('ALTER TABLE edu_training DROP wlt_teacher_survey_id');
        $this->addSql('ALTER TABLE edu_academic_year ADD wlt_organization_survey_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE edu_academic_year ADD CONSTRAINT FK_CFBE31D0D5F8DFB5 FOREIGN KEY (wlt_organization_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_CFBE31D0D5F8DFB5 ON edu_academic_year (wlt_organization_survey_id)');
        $this->addSql('ALTER TABLE edu_academic_year_audit ADD wlt_organization_survey_id INT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edu_academic_year DROP FOREIGN KEY FK_CFBE31D0D5F8DFB5');
        $this->addSql('DROP INDEX IDX_CFBE31D0D5F8DFB5 ON edu_academic_year');
        $this->addSql('ALTER TABLE edu_academic_year DROP wlt_organization_survey_id');
        $this->addSql('ALTER TABLE edu_academic_year_audit DROP wlt_organization_survey_id');
        $this->addSql('ALTER TABLE edu_training ADD wlt_teacher_survey_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE edu_training ADD CONSTRAINT FK_2692AD50F913D1F FOREIGN KEY (wlt_teacher_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_2692AD50F913D1F ON edu_training (wlt_teacher_survey_id)');
    }
}
