<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221229112412 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE wlt_agreement_activity_realization_comment (id INT AUTO_INCREMENT NOT NULL, agreement_activity_realization_id INT NOT NULL, person_id INT DEFAULT NULL, comment LONGTEXT NOT NULL, timestamp DATETIME NOT NULL, INDEX IDX_DB49388DDEF5E775 (agreement_activity_realization_id), INDEX IDX_DB49388D217BBB47 (person_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_agreement_activity_realization_comment_audit (id INT NOT NULL, rev INT NOT NULL, agreement_activity_realization_id INT DEFAULT NULL, person_id INT DEFAULT NULL, comment LONGTEXT DEFAULT NULL, timestamp DATETIME DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_935c6eb03972c07b2fc1ea23e44b8f95_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization_comment ADD CONSTRAINT FK_DB49388DDEF5E775 FOREIGN KEY (agreement_activity_realization_id) REFERENCES wlt_agreement_activity_realization (id)');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization_comment ADD CONSTRAINT FK_DB49388D217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD disabled TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization_audit ADD disabled TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE wlt_agreement_activity_realization_comment');
        $this->addSql('DROP TABLE wlt_agreement_activity_realization_comment_audit');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP disabled');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization_audit DROP disabled');
    }
}
