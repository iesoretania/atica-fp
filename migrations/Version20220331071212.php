<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220331071212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wpt_agreement_enrollment ADD additional_work_tutor_id INT DEFAULT NULL, ADD additional_educational_tutor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wpt_agreement_enrollment ADD CONSTRAINT FK_A24B4F78364A8C43 FOREIGN KEY (additional_work_tutor_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE wpt_agreement_enrollment ADD CONSTRAINT FK_A24B4F786C7EFCBC FOREIGN KEY (additional_educational_tutor_id) REFERENCES edu_teacher (id)');
        $this->addSql('CREATE INDEX IDX_A24B4F78364A8C43 ON wpt_agreement_enrollment (additional_work_tutor_id)');
        $this->addSql('CREATE INDEX IDX_A24B4F786C7EFCBC ON wpt_agreement_enrollment (additional_educational_tutor_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wpt_agreement_enrollment DROP FOREIGN KEY FK_A24B4F78364A8C43');
        $this->addSql('ALTER TABLE wpt_agreement_enrollment DROP FOREIGN KEY FK_A24B4F786C7EFCBC');
        $this->addSql('DROP INDEX IDX_A24B4F78364A8C43 ON wpt_agreement_enrollment');
        $this->addSql('DROP INDEX IDX_A24B4F786C7EFCBC ON wpt_agreement_enrollment');
        $this->addSql('ALTER TABLE wpt_agreement_enrollment DROP additional_work_tutor_id, DROP additional_educational_tutor_id');
    }
}
