<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241117105255 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_program_group DROP modality');
        $this->addSql('ALTER TABLE itp_program_group_audit DROP modality');
        $this->addSql('ALTER TABLE itp_student_program DROP modality');
        $this->addSql('ALTER TABLE itp_student_program_audit DROP modality');
        $this->addSql('ALTER TABLE itp_training_program ADD name VARCHAR(255) NOT NULL, CHANGE default_modality modality INT NOT NULL');
        $this->addSql('ALTER TABLE itp_training_program_audit ADD name VARCHAR(255) DEFAULT NULL, CHANGE default_modality modality INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_program_group ADD modality INT NOT NULL');
        $this->addSql('ALTER TABLE itp_program_group_audit ADD modality INT DEFAULT NULL');
        $this->addSql('ALTER TABLE itp_student_program ADD modality INT NOT NULL');
        $this->addSql('ALTER TABLE itp_student_program_audit ADD modality INT DEFAULT NULL');
        $this->addSql('ALTER TABLE itp_training_program DROP name, CHANGE modality default_modality INT NOT NULL');
        $this->addSql('ALTER TABLE itp_training_program_audit DROP name, CHANGE modality default_modality INT DEFAULT NULL');
    }
}
