<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240929095915 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_activity DROP FOREIGN KEY FK_12216C8B8406BD6C');
        $this->addSql('DROP INDEX IDX_12216C8B8406BD6C ON itp_activity');
        $this->addSql('ALTER TABLE itp_activity CHANGE training_program_id program_grade_id INT NOT NULL');
        $this->addSql('ALTER TABLE itp_activity ADD CONSTRAINT FK_12216C8B7F2C5D9D FOREIGN KEY (program_grade_id) REFERENCES itp_program_grade (id)');
        $this->addSql('CREATE INDEX IDX_12216C8B7F2C5D9D ON itp_activity (program_grade_id)');
        $this->addSql('ALTER TABLE itp_activity_audit CHANGE training_program_id program_grade_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE itp_training_program DROP INDEX IDX_AF5EDD7BBEFD98D1, ADD UNIQUE INDEX UNIQ_AF5EDD7BBEFD98D1 (training_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_activity DROP FOREIGN KEY FK_12216C8B7F2C5D9D');
        $this->addSql('DROP INDEX IDX_12216C8B7F2C5D9D ON itp_activity');
        $this->addSql('ALTER TABLE itp_activity CHANGE program_grade_id training_program_id INT NOT NULL');
        $this->addSql('ALTER TABLE itp_activity ADD CONSTRAINT FK_12216C8B8406BD6C FOREIGN KEY (training_program_id) REFERENCES itp_training_program (id)');
        $this->addSql('CREATE INDEX IDX_12216C8B8406BD6C ON itp_activity (training_program_id)');
        $this->addSql('ALTER TABLE itp_activity_audit CHANGE program_grade_id training_program_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE itp_training_program DROP INDEX UNIQ_AF5EDD7BBEFD98D1, ADD INDEX IDX_AF5EDD7BBEFD98D1 (training_id)');
    }
}
