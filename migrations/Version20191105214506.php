<?php

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20191105214506 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE wlt_visit_project (visit_id INT NOT NULL, project_id INT NOT NULL, INDEX IDX_985B4FC575FA0FF2 (visit_id), INDEX IDX_985B4FC5166D1F9C (project_id), PRIMARY KEY(visit_id, project_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_visit_student_enrollment (visit_id INT NOT NULL, student_enrollment_id INT NOT NULL, INDEX IDX_FEC31DBA75FA0FF2 (visit_id), INDEX IDX_FEC31DBADAE14AC5 (student_enrollment_id), PRIMARY KEY(visit_id, student_enrollment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wlt_visit_project ADD CONSTRAINT FK_985B4FC575FA0FF2 FOREIGN KEY (visit_id) REFERENCES wlt_visit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_visit_project ADD CONSTRAINT FK_985B4FC5166D1F9C FOREIGN KEY (project_id) REFERENCES wlt_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_visit_student_enrollment ADD CONSTRAINT FK_FEC31DBA75FA0FF2 FOREIGN KEY (visit_id) REFERENCES wlt_visit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_visit_student_enrollment ADD CONSTRAINT FK_FEC31DBADAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE wlt_visit_project');
        $this->addSql('DROP TABLE wlt_visit_student_enrollment');
    }
}
