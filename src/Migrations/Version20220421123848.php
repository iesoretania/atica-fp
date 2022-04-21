<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220421123848 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wlt_contact RENAME INDEX idx_952c5f7841807e1d TO IDX_8702589741807E1D');
        $this->addSql('ALTER TABLE wlt_contact RENAME INDEX idx_952c5f78a2473c4b TO IDX_87025897A2473C4B');
        $this->addSql('ALTER TABLE wlt_contact_project DROP FOREIGN KEY FK_985B4FC575FA0FF2');
        $this->addSql('DROP INDEX IDX_985B4FC575FA0FF2 ON wlt_contact_project');
        $this->addSql('ALTER TABLE wlt_contact_project DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wlt_contact_project CHANGE visit_id contact_id INT NOT NULL');
        $this->addSql('ALTER TABLE wlt_contact_project ADD CONSTRAINT FK_208AE622E7A1254A FOREIGN KEY (contact_id) REFERENCES wlt_contact (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_208AE622E7A1254A ON wlt_contact_project (contact_id)');
        $this->addSql('ALTER TABLE wlt_contact_project ADD PRIMARY KEY (contact_id, project_id)');
        $this->addSql('ALTER TABLE wlt_contact_project RENAME INDEX idx_985b4fc5166d1f9c TO IDX_208AE622166D1F9C');
        $this->addSql('ALTER TABLE wlt_contact_student_enrollment DROP FOREIGN KEY FK_FEC31DBA75FA0FF2');
        $this->addSql('DROP INDEX IDX_FEC31DBA75FA0FF2 ON wlt_contact_student_enrollment');
        $this->addSql('ALTER TABLE wlt_contact_student_enrollment DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wlt_contact_student_enrollment CHANGE visit_id contact_id INT NOT NULL');
        $this->addSql('ALTER TABLE wlt_contact_student_enrollment ADD CONSTRAINT FK_A3EF12EDE7A1254A FOREIGN KEY (contact_id) REFERENCES wlt_contact (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_A3EF12EDE7A1254A ON wlt_contact_student_enrollment (contact_id)');
        $this->addSql('ALTER TABLE wlt_contact_student_enrollment ADD PRIMARY KEY (contact_id, student_enrollment_id)');
        $this->addSql('ALTER TABLE wlt_contact_student_enrollment RENAME INDEX idx_fec31dbadae14ac5 TO IDX_A3EF12EDDAE14AC5');
        $this->addSql('ALTER TABLE wlt_contact_audit RENAME INDEX rev_f8bf02d8fb496c8df3fcc8ad9bc828b7_idx TO rev_1a3b3959a5ced6400bc91d305339a7c0_idx');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wlt_contact RENAME INDEX idx_8702589741807e1d TO IDX_952C5F7841807E1D');
        $this->addSql('ALTER TABLE wlt_contact RENAME INDEX idx_87025897a2473c4b TO IDX_952C5F78A2473C4B');
        $this->addSql('ALTER TABLE wlt_contact_audit RENAME INDEX rev_1a3b3959a5ced6400bc91d305339a7c0_idx TO rev_f8bf02d8fb496c8df3fcc8ad9bc828b7_idx');
        $this->addSql('ALTER TABLE wlt_contact_project DROP FOREIGN KEY FK_208AE622E7A1254A');
        $this->addSql('DROP INDEX IDX_208AE622E7A1254A ON wlt_contact_project');
        $this->addSql('ALTER TABLE wlt_contact_project DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wlt_contact_project CHANGE contact_id visit_id INT NOT NULL');
        $this->addSql('ALTER TABLE wlt_contact_project ADD CONSTRAINT FK_985B4FC575FA0FF2 FOREIGN KEY (visit_id) REFERENCES wlt_contact (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_985B4FC575FA0FF2 ON wlt_contact_project (visit_id)');
        $this->addSql('ALTER TABLE wlt_contact_project ADD PRIMARY KEY (visit_id, project_id)');
        $this->addSql('ALTER TABLE wlt_contact_project RENAME INDEX idx_208ae622166d1f9c TO IDX_985B4FC5166D1F9C');
        $this->addSql('ALTER TABLE wlt_contact_student_enrollment DROP FOREIGN KEY FK_A3EF12EDE7A1254A');
        $this->addSql('DROP INDEX IDX_A3EF12EDE7A1254A ON wlt_contact_student_enrollment');
        $this->addSql('ALTER TABLE wlt_contact_student_enrollment DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wlt_contact_student_enrollment CHANGE contact_id visit_id INT NOT NULL');
        $this->addSql('ALTER TABLE wlt_contact_student_enrollment ADD CONSTRAINT FK_FEC31DBA75FA0FF2 FOREIGN KEY (visit_id) REFERENCES wlt_contact (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_FEC31DBA75FA0FF2 ON wlt_contact_student_enrollment (visit_id)');
        $this->addSql('ALTER TABLE wlt_contact_student_enrollment ADD PRIMARY KEY (visit_id, student_enrollment_id)');
        $this->addSql('ALTER TABLE wlt_contact_student_enrollment RENAME INDEX idx_a3ef12eddae14ac5 TO IDX_FEC31DBADAE14AC5');
    }
}
