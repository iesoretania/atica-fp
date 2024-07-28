<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220421122325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('RENAME TABLE wlt_visit_project TO wlt_contact_project');
        $this->addSql('RENAME TABLE wlt_visit_student_enrollment TO wlt_contact_student_enrollment');
        $this->addSql('RENAME TABLE wlt_visit TO wlt_contact');
        $this->addSql('RENAME TABLE wlt_visit_audit TO wlt_contact_audit');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('RENAME TABLE wlt_contact_project TO wlt_visit_project');
        $this->addSql('RENAME TABLE wlt_contact_student_enrollment TO wlt_visit_student_enrollment');
        $this->addSql('RENAME TABLE wlt_contact TO wlt_visit');
        $this->addSql('RENAME TABLE wlt_contact_audit TO wlt_visit_audit');
    }
}
