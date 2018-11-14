<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181114213126 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE edu_subject (id INT AUTO_INCREMENT NOT NULL, grade_id INT NOT NULL, code VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_C298A968FE19A1A8 (grade_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_teaching (teacher_id INT NOT NULL, group_id INT NOT NULL, subject_id INT NOT NULL, INDEX IDX_EB30486D41807E1D (teacher_id), INDEX IDX_EB30486DFE54D947 (group_id), INDEX IDX_EB30486D23EDC87 (subject_id), PRIMARY KEY(teacher_id, group_id, subject_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE edu_subject ADD CONSTRAINT FK_C298A968FE19A1A8 FOREIGN KEY (grade_id) REFERENCES edu_grade (id)');
        $this->addSql('ALTER TABLE edu_teaching ADD CONSTRAINT FK_EB30486D41807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE edu_teaching ADD CONSTRAINT FK_EB30486DFE54D947 FOREIGN KEY (group_id) REFERENCES edu_group (id)');
        $this->addSql('ALTER TABLE edu_teaching ADD CONSTRAINT FK_EB30486D23EDC87 FOREIGN KEY (subject_id) REFERENCES edu_subject (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edu_teaching DROP FOREIGN KEY FK_EB30486D23EDC87');
        $this->addSql('DROP TABLE edu_subject');
        $this->addSql('DROP TABLE edu_teaching');
    }
}
