<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190107192214 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE wlt_activity_realization_grade (id INT AUTO_INCREMENT NOT NULL, academic_year_id INT NOT NULL, description VARCHAR(255) NOT NULL, numeric_grade INT NOT NULL, INDEX IDX_CD1FF777C54F3401 (academic_year_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wlt_activity_realization_grade ADD CONSTRAINT FK_CD1FF777C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP FOREIGN KEY FK_BD86F07824890B2B');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP FOREIGN KEY FK_BD86F078862E876A');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD id INT AUTO_INCREMENT NOT NULL, ADD grade_id INT DEFAULT NULL, ADD graded_by_id INT DEFAULT NULL, ADD graded_on DATE DEFAULT NULL, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD CONSTRAINT FK_BD86F078FE19A1A8 FOREIGN KEY (grade_id) REFERENCES wlt_activity_realization_grade (id)');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD CONSTRAINT FK_BD86F078C814BC2E FOREIGN KEY (graded_by_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD CONSTRAINT FK_BD86F07824890B2B FOREIGN KEY (agreement_id) REFERENCES wlt_agreement (id)');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD CONSTRAINT FK_BD86F078862E876A FOREIGN KEY (activity_realization_id) REFERENCES wlt_activity_realization (id)');
        $this->addSql('CREATE INDEX IDX_BD86F078FE19A1A8 ON wlt_agreement_activity_realization (grade_id)');
        $this->addSql('CREATE INDEX IDX_BD86F078C814BC2E ON wlt_agreement_activity_realization (graded_by_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP FOREIGN KEY FK_BD86F078FE19A1A8');
        $this->addSql('DROP TABLE wlt_activity_realization_grade');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP FOREIGN KEY FK_BD86F078C814BC2E');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP FOREIGN KEY FK_BD86F07824890B2B');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP FOREIGN KEY FK_BD86F078862E876A');
        $this->addSql('DROP INDEX IDX_BD86F078FE19A1A8 ON wlt_agreement_activity_realization');
        $this->addSql('DROP INDEX IDX_BD86F078C814BC2E ON wlt_agreement_activity_realization');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP id, DROP grade_id, DROP graded_by_id, DROP graded_on');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD CONSTRAINT FK_BD86F07824890B2B FOREIGN KEY (agreement_id) REFERENCES wlt_agreement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD CONSTRAINT FK_BD86F078862E876A FOREIGN KEY (activity_realization_id) REFERENCES wlt_activity_realization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD PRIMARY KEY (agreement_id, activity_realization_id)');
    }
}
