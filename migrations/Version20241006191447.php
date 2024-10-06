<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241006191447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_student_learning_program DROP FOREIGN KEY FK_3133C013CEDF9BEF');
        $this->addSql('CREATE TABLE itp_program_group_manager (program_group_id INT NOT NULL, teacher_id INT NOT NULL, INDEX IDX_843C7B3A7F612572 (program_group_id), INDEX IDX_843C7B3A41807E1D (teacher_id), PRIMARY KEY(program_group_id, teacher_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_program_group_manager_audit (program_group_id INT NOT NULL, teacher_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_1bbe35b53cfc174c39ddb9083c090a95_idx (rev), PRIMARY KEY(program_group_id, teacher_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE itp_program_group_manager ADD CONSTRAINT FK_843C7B3A7F612572 FOREIGN KEY (program_group_id) REFERENCES itp_program_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_program_group_manager ADD CONSTRAINT FK_843C7B3A41807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_training_program_group DROP FOREIGN KEY FK_E8E8C7FFE54D947');
        $this->addSql('ALTER TABLE itp_training_program_group DROP FOREIGN KEY FK_E8E8C7F8406BD6C');
        $this->addSql('ALTER TABLE itp_training_program_group_audit DROP FOREIGN KEY rev_8b067c8bbb89d1e6a3ed9816c60dbe92_fk');
        $this->addSql('ALTER TABLE itp_training_program_group_manager DROP FOREIGN KEY FK_2F200CB7CEDF9BEF');
        $this->addSql('ALTER TABLE itp_training_program_group_manager DROP FOREIGN KEY FK_2F200CB741807E1D');
        $this->addSql('DROP TABLE itp_training_program_group');
        $this->addSql('DROP TABLE itp_training_program_group_audit');
        $this->addSql('DROP TABLE itp_training_program_group_manager');
        $this->addSql('DROP TABLE itp_training_program_group_manager_audit');
        $this->addSql('ALTER TABLE itp_student_learning_program ADD CONSTRAINT FK_3133C013CEDF9BEF FOREIGN KEY (training_program_group_id) REFERENCES itp_program_group (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE itp_training_program_group (id INT AUTO_INCREMENT NOT NULL, training_program_id INT NOT NULL, group_id INT NOT NULL, locked TINYINT(1) NOT NULL, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, modality INT NOT NULL, target_hours INT DEFAULT NULL, INDEX IDX_E8E8C7F8406BD6C (training_program_id), INDEX IDX_E8E8C7FFE54D947 (group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE itp_training_program_group_audit (id INT NOT NULL, rev INT NOT NULL, training_program_id INT DEFAULT NULL, group_id INT DEFAULT NULL, locked TINYINT(1) DEFAULT NULL, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, modality INT DEFAULT NULL, target_hours INT DEFAULT NULL, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_8b067c8bbb89d1e6a3ed9816c60dbe92_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE itp_training_program_group_manager (training_program_group_id INT NOT NULL, teacher_id INT NOT NULL, INDEX IDX_2F200CB741807E1D (teacher_id), INDEX IDX_2F200CB7CEDF9BEF (training_program_group_id), PRIMARY KEY(training_program_group_id, teacher_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE itp_training_program_group_manager_audit (training_program_group_id INT NOT NULL, teacher_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_49e142fdaae7d616f4e7e17a24f844b8_idx (rev), PRIMARY KEY(training_program_group_id, teacher_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE itp_training_program_group ADD CONSTRAINT FK_E8E8C7FFE54D947 FOREIGN KEY (group_id) REFERENCES edu_group (id)');
        $this->addSql('ALTER TABLE itp_training_program_group ADD CONSTRAINT FK_E8E8C7F8406BD6C FOREIGN KEY (training_program_id) REFERENCES itp_training_program (id)');
        $this->addSql('ALTER TABLE itp_training_program_group_audit ADD CONSTRAINT rev_8b067c8bbb89d1e6a3ed9816c60dbe92_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_training_program_group_manager ADD CONSTRAINT FK_2F200CB7CEDF9BEF FOREIGN KEY (training_program_group_id) REFERENCES itp_training_program_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_training_program_group_manager ADD CONSTRAINT FK_2F200CB741807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_program_group_manager DROP FOREIGN KEY FK_843C7B3A7F612572');
        $this->addSql('ALTER TABLE itp_program_group_manager DROP FOREIGN KEY FK_843C7B3A41807E1D');
        $this->addSql('DROP TABLE itp_program_group_manager');
        $this->addSql('DROP TABLE itp_program_group_manager_audit');
        $this->addSql('ALTER TABLE itp_student_learning_program DROP FOREIGN KEY FK_3133C013CEDF9BEF');
        $this->addSql('ALTER TABLE itp_student_learning_program ADD CONSTRAINT FK_3133C013CEDF9BEF FOREIGN KEY (training_program_group_id) REFERENCES itp_training_program_group (id)');
    }
}
