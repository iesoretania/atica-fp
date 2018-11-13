<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181113113140 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE edu_department (id INT AUTO_INCREMENT NOT NULL, academic_year_id INT NOT NULL, head_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_6EB77EB1C54F3401 (academic_year_id), INDEX IDX_6EB77EB1F41A619E (head_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_teacher (id INT AUTO_INCREMENT NOT NULL, person_id INT NOT NULL, academic_year_id INT NOT NULL, department_id INT DEFAULT NULL, INDEX IDX_89A031C7217BBB47 (person_id), INDEX IDX_89A031C7C54F3401 (academic_year_id), INDEX IDX_89A031C7AE80F5DF (department_id), UNIQUE INDEX UNIQ_89A031C7217BBB47C54F3401 (person_id, academic_year_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE edu_department ADD CONSTRAINT FK_6EB77EB1C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE edu_department ADD CONSTRAINT FK_6EB77EB1F41A619E FOREIGN KEY (head_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE edu_teacher ADD CONSTRAINT FK_89A031C7217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE edu_teacher ADD CONSTRAINT FK_89A031C7C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE edu_teacher ADD CONSTRAINT FK_89A031C7AE80F5DF FOREIGN KEY (department_id) REFERENCES edu_department (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edu_teacher DROP FOREIGN KEY FK_89A031C7AE80F5DF');
        $this->addSql('ALTER TABLE edu_department DROP FOREIGN KEY FK_6EB77EB1F41A619E');
        $this->addSql('DROP TABLE edu_department');
        $this->addSql('DROP TABLE edu_teacher');
    }
}
