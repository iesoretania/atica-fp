<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241001161246 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE itp_activity_criterion (activity_id INT NOT NULL, criterion_id INT NOT NULL, INDEX IDX_5031EC0581C06096 (activity_id), INDEX IDX_5031EC0597766307 (criterion_id), PRIMARY KEY(activity_id, criterion_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_activity_criterion_audit (activity_id INT NOT NULL, criterion_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_84340deadd947925141c5ccb0e2175c4_idx (rev), PRIMARY KEY(activity_id, criterion_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE program_grade_subject (program_grade_id INT NOT NULL, subject_id INT NOT NULL, INDEX IDX_A4CABC007F2C5D9D (program_grade_id), INDEX IDX_A4CABC0023EDC87 (subject_id), PRIMARY KEY(program_grade_id, subject_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE program_grade_subject_audit (program_grade_id INT NOT NULL, subject_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_041a01c6d62e76938a2845890cdd3c63_idx (rev), PRIMARY KEY(program_grade_id, subject_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_program_grade_learning_outcome (id INT AUTO_INCREMENT NOT NULL, program_grade_id INT NOT NULL, learning_outcome_id INT NOT NULL, shared TINYINT(1) NOT NULL, INDEX IDX_99777B9D7F2C5D9D (program_grade_id), INDEX IDX_99777B9D35C2B2D5 (learning_outcome_id), UNIQUE INDEX itp_program_grade_learning_outcome_unique (program_grade_id, learning_outcome_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_program_grade_learning_outcome_audit (id INT NOT NULL, rev INT NOT NULL, program_grade_id INT DEFAULT NULL, learning_outcome_id INT DEFAULT NULL, shared TINYINT(1) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_e8feacf1d336f619b32e00ebe3fef4f3_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE itp_activity_criterion ADD CONSTRAINT FK_5031EC0581C06096 FOREIGN KEY (activity_id) REFERENCES itp_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_activity_criterion ADD CONSTRAINT FK_5031EC0597766307 FOREIGN KEY (criterion_id) REFERENCES edu_criterion (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE program_grade_subject ADD CONSTRAINT FK_A4CABC007F2C5D9D FOREIGN KEY (program_grade_id) REFERENCES itp_program_grade (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE program_grade_subject ADD CONSTRAINT FK_A4CABC0023EDC87 FOREIGN KEY (subject_id) REFERENCES edu_subject (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome ADD CONSTRAINT FK_99777B9D7F2C5D9D FOREIGN KEY (program_grade_id) REFERENCES itp_program_grade (id)');
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome ADD CONSTRAINT FK_99777B9D35C2B2D5 FOREIGN KEY (learning_outcome_id) REFERENCES edu_learning_outcome (id)');
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome_audit ADD CONSTRAINT rev_e8feacf1d336f619b32e00ebe3fef4f3_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_activity_learning_outcome DROP FOREIGN KEY FK_7A5D3C8F35C2B2D5');
        $this->addSql('ALTER TABLE itp_activity_learning_outcome DROP FOREIGN KEY FK_7A5D3C8F81C06096');
        $this->addSql('ALTER TABLE itp_activity_learning_outcome_audit DROP FOREIGN KEY rev_0ccfeaf8ab8f16c644bf5ce513cc3716_fk');
        $this->addSql('ALTER TABLE itp_activity_learning_outcome_criterion DROP FOREIGN KEY FK_682E8B5497766307');
        $this->addSql('ALTER TABLE itp_activity_learning_outcome_criterion DROP FOREIGN KEY FK_682E8B54D6217FF8');
        $this->addSql('DROP TABLE itp_activity_learning_outcome');
        $this->addSql('DROP TABLE itp_activity_learning_outcome_audit');
        $this->addSql('DROP TABLE itp_activity_learning_outcome_criterion');
        $this->addSql('DROP TABLE itp_activity_learning_outcome_criterion_audit');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE itp_activity_learning_outcome (id INT AUTO_INCREMENT NOT NULL, activity_id INT NOT NULL, learning_outcome_id INT NOT NULL, shared TINYINT(1) NOT NULL, INDEX IDX_7A5D3C8F81C06096 (activity_id), INDEX IDX_7A5D3C8F35C2B2D5 (learning_outcome_id), UNIQUE INDEX activity_learning_outcome_unique (activity_id, learning_outcome_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE itp_activity_learning_outcome_audit (id INT NOT NULL, rev INT NOT NULL, activity_id INT DEFAULT NULL, learning_outcome_id INT DEFAULT NULL, shared TINYINT(1) DEFAULT NULL, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_0ccfeaf8ab8f16c644bf5ce513cc3716_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE itp_activity_learning_outcome_criterion (activity_learning_outcome_id INT NOT NULL, criterion_id INT NOT NULL, INDEX IDX_682E8B54D6217FF8 (activity_learning_outcome_id), INDEX IDX_682E8B5497766307 (criterion_id), PRIMARY KEY(activity_learning_outcome_id, criterion_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE itp_activity_learning_outcome_criterion_audit (activity_learning_outcome_id INT NOT NULL, criterion_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_a32dde09e1d9b0b37c5a481776178c0c_idx (rev), PRIMARY KEY(activity_learning_outcome_id, criterion_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE itp_activity_learning_outcome ADD CONSTRAINT FK_7A5D3C8F35C2B2D5 FOREIGN KEY (learning_outcome_id) REFERENCES edu_learning_outcome (id)');
        $this->addSql('ALTER TABLE itp_activity_learning_outcome ADD CONSTRAINT FK_7A5D3C8F81C06096 FOREIGN KEY (activity_id) REFERENCES itp_activity (id)');
        $this->addSql('ALTER TABLE itp_activity_learning_outcome_audit ADD CONSTRAINT rev_0ccfeaf8ab8f16c644bf5ce513cc3716_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_activity_learning_outcome_criterion ADD CONSTRAINT FK_682E8B5497766307 FOREIGN KEY (criterion_id) REFERENCES edu_criterion (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_activity_learning_outcome_criterion ADD CONSTRAINT FK_682E8B54D6217FF8 FOREIGN KEY (activity_learning_outcome_id) REFERENCES itp_activity_learning_outcome (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_activity_criterion DROP FOREIGN KEY FK_5031EC0581C06096');
        $this->addSql('ALTER TABLE itp_activity_criterion DROP FOREIGN KEY FK_5031EC0597766307');
        $this->addSql('ALTER TABLE program_grade_subject DROP FOREIGN KEY FK_A4CABC007F2C5D9D');
        $this->addSql('ALTER TABLE program_grade_subject DROP FOREIGN KEY FK_A4CABC0023EDC87');
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome DROP FOREIGN KEY FK_99777B9D7F2C5D9D');
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome DROP FOREIGN KEY FK_99777B9D35C2B2D5');
        $this->addSql('ALTER TABLE itp_program_grade_learning_outcome_audit DROP FOREIGN KEY rev_e8feacf1d336f619b32e00ebe3fef4f3_fk');
        $this->addSql('DROP TABLE itp_activity_criterion');
        $this->addSql('DROP TABLE itp_activity_criterion_audit');
        $this->addSql('DROP TABLE program_grade_subject');
        $this->addSql('DROP TABLE program_grade_subject_audit');
        $this->addSql('DROP TABLE itp_program_grade_learning_outcome');
        $this->addSql('DROP TABLE itp_program_grade_learning_outcome_audit');
    }
}
