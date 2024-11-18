<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241118084905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_training_program DROP FOREIGN KEY FK_AF5EDD7BBEFD98D1');
        $this->addSql('DROP INDEX UNIQ_AF5EDD7BBEFD98D1 ON itp_training_program');
        $this->addSql('ALTER TABLE itp_training_program DROP training_id');
        $this->addSql('ALTER TABLE itp_training_program_audit DROP training_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_training_program ADD training_id INT NOT NULL');
        $this->addSql('ALTER TABLE itp_training_program ADD CONSTRAINT FK_AF5EDD7BBEFD98D1 FOREIGN KEY (training_id) REFERENCES edu_training (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AF5EDD7BBEFD98D1 ON itp_training_program (training_id)');
        $this->addSql('ALTER TABLE itp_training_program_audit ADD training_id INT DEFAULT NULL');
    }
}
