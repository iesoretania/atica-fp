<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220421172825 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE edu_contact_method (id INT AUTO_INCREMENT NOT NULL, academic_year_id INT NOT NULL, description VARCHAR(255) NOT NULL, INDEX IDX_4992BBDC54F3401 (academic_year_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_contact_method_audit (id INT NOT NULL, rev INT NOT NULL, academic_year_id INT DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_f0caa067d32baecb08ec4f3fc820d0a6_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE edu_contact_method ADD CONSTRAINT FK_4992BBDC54F3401 FOREIGN KEY (academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE wlt_contact ADD method_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wlt_contact ADD CONSTRAINT FK_8702589719883967 FOREIGN KEY (method_id) REFERENCES edu_contact_method (id)');
        $this->addSql('CREATE INDEX IDX_8702589719883967 ON wlt_contact (method_id)');
        $this->addSql('ALTER TABLE wlt_contact_audit ADD method_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wlt_contact DROP FOREIGN KEY FK_8702589719883967');
        $this->addSql('DROP TABLE edu_contact_method');
        $this->addSql('DROP TABLE edu_contact_method_audit');
        $this->addSql('DROP INDEX IDX_8702589719883967 ON wlt_contact');
        $this->addSql('ALTER TABLE wlt_contact DROP method_id');
        $this->addSql('ALTER TABLE wlt_contact_audit DROP method_id');
    }
}
