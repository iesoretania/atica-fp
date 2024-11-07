<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241107111652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_student_program_workcenter ADD work_tutor_remarks LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_audit ADD work_tutor_remarks LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_student_program_workcenter DROP work_tutor_remarks');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_audit DROP work_tutor_remarks');
    }
}
