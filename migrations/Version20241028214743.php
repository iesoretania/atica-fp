<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241028214743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_training_program ADD weekly_activity_report_template_type INT DEFAULT 0');
        $this->addSql('ALTER TABLE itp_training_program CHANGE weekly_activity_report_template_type weekly_activity_report_template_type INT NOT NULL');
        $this->addSql('ALTER TABLE itp_training_program_audit ADD weekly_activity_report_template_type INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_training_program DROP weekly_activity_report_template_type');
        $this->addSql('ALTER TABLE itp_training_program_audit DROP weekly_activity_report_template_type');
    }
}
