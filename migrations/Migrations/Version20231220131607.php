<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231220131607 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('RENAME TABLE wpt_visit_agreement TO wpt_contact_agreement');
        $this->addSql('RENAME TABLE wpt_visit_student_enrollment TO wpt_contact_student_enrollment');
        $this->addSql('RENAME TABLE wpt_visit TO wpt_contact');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('RENAME TABLE wpt_contact_agreement TO wpt_visit_agreement');
        $this->addSql('RENAME TABLE wpt_contact_student_enrollment TO wpt_visit_student_enrollment');
        $this->addSql('RENAME TABLE wpt_contact TO wpt_visit');
    }
}
