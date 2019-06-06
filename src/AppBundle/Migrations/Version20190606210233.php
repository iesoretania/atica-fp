<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190606210233 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE wlt_teacher_survey');
        $this->addSql('DROP TABLE wlt_teacher_survey_audit');
        $this->addSql('ALTER TABLE edu_teacher ADD wlt_teacher_survey_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE edu_teacher ADD CONSTRAINT FK_89A031C7F913D1F FOREIGN KEY (wlt_teacher_survey_id) REFERENCES answered_survey (id)');
        $this->addSql('CREATE INDEX IDX_89A031C7F913D1F ON edu_teacher (wlt_teacher_survey_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE wlt_teacher_survey (id INT AUTO_INCREMENT NOT NULL, teacher_id INT NOT NULL, answered_survey_id INT NOT NULL, UNIQUE INDEX UNIQ_118986C141807E1DA97283E6 (teacher_id, answered_survey_id), INDEX IDX_118986C141807E1D (teacher_id), INDEX IDX_118986C1A97283E6 (answered_survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_teacher_survey_audit (id INT NOT NULL, rev INT NOT NULL, teacher_id INT DEFAULT NULL, answered_survey_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL COLLATE utf8mb4_spanish_ci, INDEX rev_75c9176e5fb788ba65cb46932d654fed_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wlt_teacher_survey ADD CONSTRAINT FK_118986C141807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE wlt_teacher_survey ADD CONSTRAINT FK_118986C1A97283E6 FOREIGN KEY (answered_survey_id) REFERENCES answered_survey (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE edu_teacher DROP FOREIGN KEY FK_89A031C7F913D1F');
        $this->addSql('DROP INDEX IDX_89A031C7F913D1F ON edu_teacher');
        $this->addSql('ALTER TABLE edu_teacher DROP wlt_teacher_survey_id');
    }
}
