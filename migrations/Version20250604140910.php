<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250604140910 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE edu_performance_scale_value ADD negative_grade TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE edu_performance_scale_value_audit ADD negative_grade TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE edu_performance_scale_value DROP negative_grade');
        $this->addSql('ALTER TABLE edu_performance_scale_value_audit DROP negative_grade');
    }
}
