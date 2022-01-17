<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200305125141 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE wpt_visit (id INT AUTO_INCREMENT NOT NULL, teacher_id INT NOT NULL, workcenter_id INT NOT NULL, date_time DATETIME NOT NULL, detail LONGTEXT DEFAULT NULL, INDEX IDX_BE387B1C41807E1D (teacher_id), INDEX IDX_BE387B1CA2473C4B (workcenter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_visit_agreement (visit_id INT NOT NULL, agreement_id INT NOT NULL, INDEX IDX_AFFBB6275FA0FF2 (visit_id), INDEX IDX_AFFBB6224890B2B (agreement_id), PRIMARY KEY(visit_id, agreement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_visit_student_enrollment (visit_id INT NOT NULL, student_enrollment_id INT NOT NULL, INDEX IDX_3740B2075FA0FF2 (visit_id), INDEX IDX_3740B20DAE14AC5 (student_enrollment_id), PRIMARY KEY(visit_id, student_enrollment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wpt_visit ADD CONSTRAINT FK_BE387B1C41807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE wpt_visit ADD CONSTRAINT FK_BE387B1CA2473C4B FOREIGN KEY (workcenter_id) REFERENCES workcenter (id)');
        $this->addSql('ALTER TABLE wpt_visit_agreement ADD CONSTRAINT FK_AFFBB6275FA0FF2 FOREIGN KEY (visit_id) REFERENCES wpt_visit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wpt_visit_agreement ADD CONSTRAINT FK_AFFBB6224890B2B FOREIGN KEY (agreement_id) REFERENCES wpt_agreement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wpt_visit_student_enrollment ADD CONSTRAINT FK_3740B2075FA0FF2 FOREIGN KEY (visit_id) REFERENCES wpt_visit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wpt_visit_student_enrollment ADD CONSTRAINT FK_3740B20DAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wpt_visit_agreement DROP FOREIGN KEY FK_AFFBB6275FA0FF2');
        $this->addSql('ALTER TABLE wpt_visit_student_enrollment DROP FOREIGN KEY FK_3740B2075FA0FF2');
        $this->addSql('DROP TABLE wpt_visit');
        $this->addSql('DROP TABLE wpt_visit_agreement');
        $this->addSql('DROP TABLE wpt_visit_student_enrollment');
    }
}
