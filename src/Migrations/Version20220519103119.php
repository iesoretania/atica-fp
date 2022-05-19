<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220519103119 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE edu_contact_method CHANGE enabled enabled TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE wlt_agreement ADD work_tutor_remarks LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE edu_contact_method CHANGE enabled enabled TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE wlt_agreement DROP work_tutor_remarks');
    }
}
