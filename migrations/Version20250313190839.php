<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250313190839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE wpt_shift SET hours = hours * 100');
        $this->addSql('UPDATE wpt_work_day SET hours = hours * 100');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE wpt_work_day SET hours = hours / 100');
        $this->addSql('UPDATE wpt_shift SET hours = hours / 100');
    }
}
