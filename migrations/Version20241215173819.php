<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241215173819 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome DROP FOREIGN KEY FK_99777B9D35C2B2D5');
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome DROP FOREIGN KEY FK_99777B9D7F2C5D9D');
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome_audit DROP FOREIGN KEY rev_e8feacf1d336f619b32e00ebe3fef4f3_fk');
        $this->addSql('ALTER TABLE itp_program_grade_subject DROP FOREIGN KEY FK_F99BA61723EDC87');
        $this->addSql('ALTER TABLE itp_program_grade_subject DROP FOREIGN KEY FK_F99BA6177F2C5D9D');
        $this->addSql('DROP TABLE itp_program_grade_learning_outcome');
        $this->addSql('DROP TABLE itp_program_grade_learning_outcome_audit');
        $this->addSql('DROP TABLE itp_program_grade_subject');
        $this->addSql('DROP TABLE itp_program_grade_subject_audit');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE itp_program_grade_learning_outcome (id INT AUTO_INCREMENT NOT NULL, program_grade_id INT NOT NULL, learning_outcome_id INT NOT NULL, shared TINYINT(1) NOT NULL, INDEX IDX_99777B9D35C2B2D5 (learning_outcome_id), UNIQUE INDEX itp_program_grade_learning_outcome_unique (program_grade_id, learning_outcome_id), INDEX IDX_99777B9D7F2C5D9D (program_grade_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE itp_program_grade_learning_outcome_audit (id INT NOT NULL, rev INT NOT NULL, program_grade_id INT DEFAULT NULL, learning_outcome_id INT DEFAULT NULL, shared TINYINT(1) DEFAULT NULL, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_e8feacf1d336f619b32e00ebe3fef4f3_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE itp_program_grade_subject (program_grade_id INT NOT NULL, subject_id INT NOT NULL, INDEX IDX_F99BA6177F2C5D9D (program_grade_id), INDEX IDX_F99BA61723EDC87 (subject_id), PRIMARY KEY(program_grade_id, subject_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE itp_program_grade_subject_audit (program_grade_id INT NOT NULL, subject_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_852e30195ad8226f58e88e263093029c_idx (rev), PRIMARY KEY(program_grade_id, subject_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome ADD CONSTRAINT FK_99777B9D35C2B2D5 FOREIGN KEY (learning_outcome_id) REFERENCES edu_learning_outcome (id)');
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome ADD CONSTRAINT FK_99777B9D7F2C5D9D FOREIGN KEY (program_grade_id) REFERENCES itp_program_grade (id)');
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome_audit ADD CONSTRAINT rev_e8feacf1d336f619b32e00ebe3fef4f3_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_program_grade_subject ADD CONSTRAINT FK_F99BA61723EDC87 FOREIGN KEY (subject_id) REFERENCES edu_subject (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_program_grade_subject ADD CONSTRAINT FK_F99BA6177F2C5D9D FOREIGN KEY (program_grade_id) REFERENCES itp_program_grade (id) ON DELETE CASCADE');
    }
}
