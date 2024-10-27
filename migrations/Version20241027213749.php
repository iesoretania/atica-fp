<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241027213749 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_student_program_activity ADD student_program_workcenter_id INT NOT NULL');
        $this->addSql('ALTER TABLE itp_student_program_activity ADD CONSTRAINT FK_4A38FA0BF0C2682D FOREIGN KEY (student_program_workcenter_id) REFERENCES itp_student_program_workcenter (id)');
        $this->addSql('CREATE INDEX IDX_4A38FA0BF0C2682D ON itp_student_program_activity (student_program_workcenter_id)');
        $this->addSql('ALTER TABLE itp_student_program_activity_audit ADD student_program_workcenter_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_student_program_activity DROP FOREIGN KEY FK_4A38FA0BF0C2682D');
        $this->addSql('DROP INDEX IDX_4A38FA0BF0C2682D ON itp_student_program_activity');
        $this->addSql('ALTER TABLE itp_student_program_activity DROP student_program_workcenter_id');
        $this->addSql('ALTER TABLE itp_student_program_activity_audit DROP student_program_workcenter_id');
    }
}
