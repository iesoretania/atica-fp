<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241010102231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_student_program_workcenter ADD educational_tutor_id INT NOT NULL, ADD additional_educational_tutor_id INT DEFAULT NULL, ADD work_tutor_id INT NOT NULL, ADD additional_work_tutor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE itp_student_program_workcenter ADD CONSTRAINT FK_65275568E7F72E80 FOREIGN KEY (educational_tutor_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE itp_student_program_workcenter ADD CONSTRAINT FK_652755686C7EFCBC FOREIGN KEY (additional_educational_tutor_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE itp_student_program_workcenter ADD CONSTRAINT FK_65275568F53AEEAD FOREIGN KEY (work_tutor_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE itp_student_program_workcenter ADD CONSTRAINT FK_65275568364A8C43 FOREIGN KEY (additional_work_tutor_id) REFERENCES person (id)');
        $this->addSql('CREATE INDEX IDX_65275568E7F72E80 ON itp_student_program_workcenter (educational_tutor_id)');
        $this->addSql('CREATE INDEX IDX_652755686C7EFCBC ON itp_student_program_workcenter (additional_educational_tutor_id)');
        $this->addSql('CREATE INDEX IDX_65275568F53AEEAD ON itp_student_program_workcenter (work_tutor_id)');
        $this->addSql('CREATE INDEX IDX_65275568364A8C43 ON itp_student_program_workcenter (additional_work_tutor_id)');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_audit ADD educational_tutor_id INT DEFAULT NULL, ADD additional_educational_tutor_id INT DEFAULT NULL, ADD work_tutor_id INT DEFAULT NULL, ADD additional_work_tutor_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_student_program_workcenter DROP FOREIGN KEY FK_65275568E7F72E80');
        $this->addSql('ALTER TABLE itp_student_program_workcenter DROP FOREIGN KEY FK_652755686C7EFCBC');
        $this->addSql('ALTER TABLE itp_student_program_workcenter DROP FOREIGN KEY FK_65275568F53AEEAD');
        $this->addSql('ALTER TABLE itp_student_program_workcenter DROP FOREIGN KEY FK_65275568364A8C43');
        $this->addSql('DROP INDEX IDX_65275568E7F72E80 ON itp_student_program_workcenter');
        $this->addSql('DROP INDEX IDX_652755686C7EFCBC ON itp_student_program_workcenter');
        $this->addSql('DROP INDEX IDX_65275568F53AEEAD ON itp_student_program_workcenter');
        $this->addSql('DROP INDEX IDX_65275568364A8C43 ON itp_student_program_workcenter');
        $this->addSql('ALTER TABLE itp_student_program_workcenter DROP educational_tutor_id, DROP additional_educational_tutor_id, DROP work_tutor_id, DROP additional_work_tutor_id');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_audit DROP educational_tutor_id, DROP additional_educational_tutor_id, DROP work_tutor_id, DROP additional_work_tutor_id');
    }
}
