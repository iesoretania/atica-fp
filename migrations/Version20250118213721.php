<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250118213721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE itp_contact (id INT AUTO_INCREMENT NOT NULL, teacher_id INT NOT NULL, workcenter_id INT NOT NULL, method_id INT DEFAULT NULL, date_time DATETIME NOT NULL, detail LONGTEXT DEFAULT NULL, INDEX IDX_1217274A41807E1D (teacher_id), INDEX IDX_1217274AA2473C4B (workcenter_id), INDEX IDX_1217274A19883967 (method_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_contact_training_program (contact_id INT NOT NULL, training_program_id INT NOT NULL, INDEX IDX_3B01C30FE7A1254A (contact_id), INDEX IDX_3B01C30F8406BD6C (training_program_id), PRIMARY KEY(contact_id, training_program_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_contact_student_enrollment (contact_id INT NOT NULL, student_enrollment_id INT NOT NULL, INDEX IDX_4CF4C60DE7A1254A (contact_id), INDEX IDX_4CF4C60DDAE14AC5 (student_enrollment_id), PRIMARY KEY(contact_id, student_enrollment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_contact_audit (id INT NOT NULL, rev INT NOT NULL, teacher_id INT DEFAULT NULL, workcenter_id INT DEFAULT NULL, method_id INT DEFAULT NULL, date_time DATETIME DEFAULT NULL, detail LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_f7bd140f4ac13ef88636d1bbd6394d2d_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_contact_training_program_audit (contact_id INT NOT NULL, training_program_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_75bfc426b9bd672949b090d7f5cc54d8_idx (rev), PRIMARY KEY(contact_id, training_program_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_contact_student_enrollment_audit (contact_id INT NOT NULL, student_enrollment_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_c63c374aec0e3b506b7573a2dac45d48_idx (rev), PRIMARY KEY(contact_id, student_enrollment_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE itp_contact ADD CONSTRAINT FK_1217274A41807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE itp_contact ADD CONSTRAINT FK_1217274AA2473C4B FOREIGN KEY (workcenter_id) REFERENCES workcenter (id)');
        $this->addSql('ALTER TABLE itp_contact ADD CONSTRAINT FK_1217274A19883967 FOREIGN KEY (method_id) REFERENCES edu_contact_method (id)');
        $this->addSql('ALTER TABLE itp_contact_training_program ADD CONSTRAINT FK_3B01C30FE7A1254A FOREIGN KEY (contact_id) REFERENCES itp_contact (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_contact_training_program ADD CONSTRAINT FK_3B01C30F8406BD6C FOREIGN KEY (training_program_id) REFERENCES itp_training_program (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_contact_student_enrollment ADD CONSTRAINT FK_4CF4C60DE7A1254A FOREIGN KEY (contact_id) REFERENCES itp_contact (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_contact_student_enrollment ADD CONSTRAINT FK_4CF4C60DDAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_contact_audit ADD CONSTRAINT rev_f7bd140f4ac13ef88636d1bbd6394d2d_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_contact DROP FOREIGN KEY FK_1217274A41807E1D');
        $this->addSql('ALTER TABLE itp_contact DROP FOREIGN KEY FK_1217274AA2473C4B');
        $this->addSql('ALTER TABLE itp_contact DROP FOREIGN KEY FK_1217274A19883967');
        $this->addSql('ALTER TABLE itp_contact_training_program DROP FOREIGN KEY FK_3B01C30FE7A1254A');
        $this->addSql('ALTER TABLE itp_contact_training_program DROP FOREIGN KEY FK_3B01C30F8406BD6C');
        $this->addSql('ALTER TABLE itp_contact_student_enrollment DROP FOREIGN KEY FK_4CF4C60DE7A1254A');
        $this->addSql('ALTER TABLE itp_contact_student_enrollment DROP FOREIGN KEY FK_4CF4C60DDAE14AC5');
        $this->addSql('ALTER TABLE itp_contact_audit DROP FOREIGN KEY rev_f7bd140f4ac13ef88636d1bbd6394d2d_fk');
        $this->addSql('DROP TABLE itp_contact');
        $this->addSql('DROP TABLE itp_contact_training_program');
        $this->addSql('DROP TABLE itp_contact_student_enrollment');
        $this->addSql('DROP TABLE itp_contact_audit');
        $this->addSql('DROP TABLE itp_contact_training_program_audit');
        $this->addSql('DROP TABLE itp_contact_student_enrollment_audit');
    }
}
