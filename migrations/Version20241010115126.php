<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241010115126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_student_program_workcenter ADD start_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', ADD end_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_audit ADD start_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', ADD end_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_student_program_workcenter DROP start_date, DROP end_date');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_audit DROP start_date, DROP end_date');
    }
}
