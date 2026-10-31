<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261031213534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'DBAL 4: elimina los comentarios (DC2Type:...) de las columnas de fecha inmutables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_student_program_workcenter CHANGE start_date start_date DATE DEFAULT NULL, CHANGE end_date end_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_audit CHANGE start_date start_date DATE DEFAULT NULL, CHANGE end_date end_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity_comment CHANGE timestamp timestamp DATETIME NOT NULL');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity_comment_audit CHANGE timestamp timestamp DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_student_program_workcenter CHANGE start_date start_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE end_date end_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity_comment CHANGE timestamp timestamp DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity_comment_audit CHANGE timestamp timestamp DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_audit CHANGE start_date start_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE end_date end_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
    }
}
