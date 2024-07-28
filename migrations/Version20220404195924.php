<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220404195924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wlt_agreement ADD additional_work_tutor_id INT DEFAULT NULL, ADD additional_educational_tutor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wlt_agreement ADD CONSTRAINT FK_2B23AFE9364A8C43 FOREIGN KEY (additional_work_tutor_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE wlt_agreement ADD CONSTRAINT FK_2B23AFE96C7EFCBC FOREIGN KEY (additional_educational_tutor_id) REFERENCES edu_teacher (id)');
        $this->addSql('CREATE INDEX IDX_2B23AFE9364A8C43 ON wlt_agreement (additional_work_tutor_id)');
        $this->addSql('CREATE INDEX IDX_2B23AFE96C7EFCBC ON wlt_agreement (additional_educational_tutor_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wlt_agreement DROP FOREIGN KEY FK_2B23AFE9364A8C43');
        $this->addSql('ALTER TABLE wlt_agreement DROP FOREIGN KEY FK_2B23AFE96C7EFCBC');
        $this->addSql('DROP INDEX IDX_2B23AFE9364A8C43 ON wlt_agreement');
        $this->addSql('DROP INDEX IDX_2B23AFE96C7EFCBC ON wlt_agreement');
        $this->addSql('ALTER TABLE wlt_agreement DROP additional_work_tutor_id, DROP additional_educational_tutor_id');
    }
}
