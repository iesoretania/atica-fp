<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181113122108 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE edu_grade (id INT AUTO_INCREMENT NOT NULL, training_id INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_78AC6283BEFD98D1 (training_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_group (id INT AUTO_INCREMENT NOT NULL, grade_id INT NOT NULL, tutor_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_4C368872FE19A1A8 (grade_id), INDEX IDX_4C368872208F64F1 (tutor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_training (id INT AUTO_INCREMENT NOT NULL, academic_year_id INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_2692AD50C54F3401 (academic_year_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE edu_grade ADD CONSTRAINT FK_78AC6283BEFD98D1 FOREIGN KEY (training_id) REFERENCES edu_training (id)');
        $this->addSql('ALTER TABLE edu_group ADD CONSTRAINT FK_4C368872FE19A1A8 FOREIGN KEY (grade_id) REFERENCES edu_grade (id)');
        $this->addSql('ALTER TABLE edu_group ADD CONSTRAINT FK_4C368872208F64F1 FOREIGN KEY (tutor_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE edu_training ADD CONSTRAINT FK_2692AD50C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edu_group DROP FOREIGN KEY FK_4C368872FE19A1A8');
        $this->addSql('ALTER TABLE edu_grade DROP FOREIGN KEY FK_78AC6283BEFD98D1');
        $this->addSql('DROP TABLE edu_grade');
        $this->addSql('DROP TABLE edu_group');
        $this->addSql('DROP TABLE edu_training');
    }
}
