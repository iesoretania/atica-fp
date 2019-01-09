<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190109164403 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE wlt_meeting (id INT AUTO_INCREMENT NOT NULL, academic_year_id INT NOT NULL, date DATE NOT NULL, detail LONGTEXT DEFAULT NULL, INDEX IDX_3E755F96C54F3401 (academic_year_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_meeting_student_enrollment (meeting_id INT NOT NULL, student_enrollment_id INT NOT NULL, INDEX IDX_A19B8C2067433D9C (meeting_id), INDEX IDX_A19B8C20DAE14AC5 (student_enrollment_id), PRIMARY KEY(meeting_id, student_enrollment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_meeting_teacher (meeting_id INT NOT NULL, teacher_id INT NOT NULL, INDEX IDX_BD32F91567433D9C (meeting_id), INDEX IDX_BD32F91541807E1D (teacher_id), PRIMARY KEY(meeting_id, teacher_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wlt_meeting ADD CONSTRAINT FK_3E755F96C54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE wlt_meeting_student_enrollment ADD CONSTRAINT FK_A19B8C2067433D9C FOREIGN KEY (meeting_id) REFERENCES wlt_meeting (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_meeting_student_enrollment ADD CONSTRAINT FK_A19B8C20DAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_meeting_teacher ADD CONSTRAINT FK_BD32F91567433D9C FOREIGN KEY (meeting_id) REFERENCES wlt_meeting (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_meeting_teacher ADD CONSTRAINT FK_BD32F91541807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_meeting_student_enrollment DROP FOREIGN KEY FK_A19B8C2067433D9C');
        $this->addSql('ALTER TABLE wlt_meeting_teacher DROP FOREIGN KEY FK_BD32F91567433D9C');
        $this->addSql('DROP TABLE wlt_meeting');
        $this->addSql('DROP TABLE wlt_meeting_student_enrollment');
        $this->addSql('DROP TABLE wlt_meeting_teacher');
    }
}
