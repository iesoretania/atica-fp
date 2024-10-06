<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241006120214 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_company_program DROP FOREIGN KEY FK_EA6C82598406BD6C');
        $this->addSql('DROP INDEX IDX_EA6C82598406BD6C ON itp_company_program');
        $this->addSql('ALTER TABLE itp_company_program CHANGE training_program_id program_grade_id INT NOT NULL');
        $this->addSql('ALTER TABLE itp_company_program ADD CONSTRAINT FK_EA6C82597F2C5D9D FOREIGN KEY (program_grade_id) REFERENCES itp_program_grade (id)');
        $this->addSql('CREATE INDEX IDX_EA6C82597F2C5D9D ON itp_company_program (program_grade_id)');
        $this->addSql('ALTER TABLE itp_company_program_audit CHANGE training_program_id program_grade_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_company_program DROP FOREIGN KEY FK_EA6C82597F2C5D9D');
        $this->addSql('DROP INDEX IDX_EA6C82597F2C5D9D ON itp_company_program');
        $this->addSql('ALTER TABLE itp_company_program CHANGE program_grade_id training_program_id INT NOT NULL');
        $this->addSql('ALTER TABLE itp_company_program ADD CONSTRAINT FK_EA6C82598406BD6C FOREIGN KEY (training_program_id) REFERENCES itp_training_program (id)');
        $this->addSql('CREATE INDEX IDX_EA6C82598406BD6C ON itp_company_program (training_program_id)');
        $this->addSql('ALTER TABLE itp_company_program_audit CHANGE program_grade_id training_program_id INT DEFAULT NULL');
    }
}
