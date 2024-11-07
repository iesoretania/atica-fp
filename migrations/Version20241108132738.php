<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241108132738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity_comment ADD person_id INT NOT NULL, ADD comment LONGTEXT NOT NULL, ADD timestamp DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity_comment ADD CONSTRAINT FK_1A88C1A217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('CREATE INDEX IDX_1A88C1A217BBB47 ON itp_student_program_workcenter_activity_comment (person_id)');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity_comment_audit ADD person_id INT DEFAULT NULL, ADD comment LONGTEXT DEFAULT NULL, ADD timestamp DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity_comment DROP FOREIGN KEY FK_1A88C1A217BBB47');
        $this->addSql('DROP INDEX IDX_1A88C1A217BBB47 ON itp_student_program_workcenter_activity_comment');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity_comment DROP person_id, DROP comment, DROP timestamp');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity_comment_audit DROP person_id, DROP comment, DROP timestamp');
    }
}
