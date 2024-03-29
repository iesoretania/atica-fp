<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231220132031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE wpt_contact_audit (id INT NOT NULL, rev INT NOT NULL, teacher_id INT DEFAULT NULL, workcenter_id INT DEFAULT NULL, method_id INT DEFAULT NULL, date_time DATETIME DEFAULT NULL, detail LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_14a73b1dc586567879897aac041a9700_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wpt_contact DROP FOREIGN KEY FK_BE387B1C19883967');
        $this->addSql('ALTER TABLE wpt_contact DROP FOREIGN KEY FK_BE387B1C41807E1D');
        $this->addSql('ALTER TABLE wpt_contact DROP FOREIGN KEY FK_BE387B1CA2473C4B');
        $this->addSql('DROP INDEX idx_be387b1c41807e1d ON wpt_contact');
        $this->addSql('CREATE INDEX IDX_BA9039F141807E1D ON wpt_contact (teacher_id)');
        $this->addSql('DROP INDEX idx_be387b1ca2473c4b ON wpt_contact');
        $this->addSql('CREATE INDEX IDX_BA9039F1A2473C4B ON wpt_contact (workcenter_id)');
        $this->addSql('DROP INDEX idx_be387b1c19883967 ON wpt_contact');
        $this->addSql('CREATE INDEX IDX_BA9039F119883967 ON wpt_contact (method_id)');
        $this->addSql('ALTER TABLE wpt_contact ADD CONSTRAINT FK_BE387B1C19883967 FOREIGN KEY (method_id) REFERENCES edu_contact_method (id)');
        $this->addSql('ALTER TABLE wpt_contact ADD CONSTRAINT FK_BE387B1C41807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE wpt_contact ADD CONSTRAINT FK_BE387B1CA2473C4B FOREIGN KEY (workcenter_id) REFERENCES workcenter (id)');
        $this->addSql('ALTER TABLE wpt_contact_agreement DROP FOREIGN KEY FK_AFFBB6275FA0FF2');
        $this->addSql('DROP INDEX IDX_AFFBB6275FA0FF2 ON wpt_contact_agreement');
        $this->addSql('ALTER TABLE wpt_contact_agreement DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wpt_contact_agreement DROP FOREIGN KEY FK_AFFBB6224890B2B');
        $this->addSql('ALTER TABLE wpt_contact_agreement CHANGE visit_id contact_id INT NOT NULL');
        $this->addSql('ALTER TABLE wpt_contact_agreement ADD CONSTRAINT FK_B560CE08E7A1254A FOREIGN KEY (contact_id) REFERENCES wpt_contact (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_B560CE08E7A1254A ON wpt_contact_agreement (contact_id)');
        $this->addSql('ALTER TABLE wpt_contact_agreement ADD PRIMARY KEY (contact_id, agreement_id)');
        $this->addSql('DROP INDEX idx_affbb6224890b2b ON wpt_contact_agreement');
        $this->addSql('CREATE INDEX IDX_B560CE0824890B2B ON wpt_contact_agreement (agreement_id)');
        $this->addSql('ALTER TABLE wpt_contact_agreement ADD CONSTRAINT FK_AFFBB6224890B2B FOREIGN KEY (agreement_id) REFERENCES wpt_agreement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wpt_contact_student_enrollment DROP FOREIGN KEY FK_3740B2075FA0FF2');
        $this->addSql('DROP INDEX IDX_3740B2075FA0FF2 ON wpt_contact_student_enrollment');
        $this->addSql('ALTER TABLE wpt_contact_student_enrollment DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wpt_contact_student_enrollment DROP FOREIGN KEY FK_3740B20DAE14AC5');
        $this->addSql('ALTER TABLE wpt_contact_student_enrollment CHANGE visit_id contact_id INT NOT NULL');
        $this->addSql('ALTER TABLE wpt_contact_student_enrollment ADD CONSTRAINT FK_DC95389BE7A1254A FOREIGN KEY (contact_id) REFERENCES wpt_contact (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_DC95389BE7A1254A ON wpt_contact_student_enrollment (contact_id)');
        $this->addSql('ALTER TABLE wpt_contact_student_enrollment ADD PRIMARY KEY (contact_id, student_enrollment_id)');
        $this->addSql('DROP INDEX idx_3740b20dae14ac5 ON wpt_contact_student_enrollment');
        $this->addSql('CREATE INDEX IDX_DC95389BDAE14AC5 ON wpt_contact_student_enrollment (student_enrollment_id)');
        $this->addSql('ALTER TABLE wpt_contact_student_enrollment ADD CONSTRAINT FK_3740B20DAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE wpt_contact_audit');
        $this->addSql('ALTER TABLE wpt_contact DROP FOREIGN KEY FK_BA9039F141807E1D');
        $this->addSql('ALTER TABLE wpt_contact DROP FOREIGN KEY FK_BA9039F1A2473C4B');
        $this->addSql('ALTER TABLE wpt_contact DROP FOREIGN KEY FK_BA9039F119883967');
        $this->addSql('DROP INDEX idx_ba9039f1a2473c4b ON wpt_contact');
        $this->addSql('CREATE INDEX IDX_BE387B1CA2473C4B ON wpt_contact (workcenter_id)');
        $this->addSql('DROP INDEX idx_ba9039f141807e1d ON wpt_contact');
        $this->addSql('CREATE INDEX IDX_BE387B1C41807E1D ON wpt_contact (teacher_id)');
        $this->addSql('DROP INDEX idx_ba9039f119883967 ON wpt_contact');
        $this->addSql('CREATE INDEX IDX_BE387B1C19883967 ON wpt_contact (method_id)');
        $this->addSql('ALTER TABLE wpt_contact ADD CONSTRAINT FK_BA9039F141807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE wpt_contact ADD CONSTRAINT FK_BA9039F1A2473C4B FOREIGN KEY (workcenter_id) REFERENCES workcenter (id)');
        $this->addSql('ALTER TABLE wpt_contact ADD CONSTRAINT FK_BA9039F119883967 FOREIGN KEY (method_id) REFERENCES edu_contact_method (id)');
        $this->addSql('ALTER TABLE wpt_contact_agreement DROP FOREIGN KEY FK_B560CE08E7A1254A');
        $this->addSql('DROP INDEX IDX_B560CE08E7A1254A ON wpt_contact_agreement');
        $this->addSql('ALTER TABLE wpt_contact_agreement DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wpt_contact_agreement DROP FOREIGN KEY FK_B560CE0824890B2B');
        $this->addSql('ALTER TABLE wpt_contact_agreement CHANGE contact_id visit_id INT NOT NULL');
        $this->addSql('ALTER TABLE wpt_contact_agreement ADD CONSTRAINT FK_AFFBB6275FA0FF2 FOREIGN KEY (visit_id) REFERENCES wpt_contact (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_AFFBB6275FA0FF2 ON wpt_contact_agreement (visit_id)');
        $this->addSql('ALTER TABLE wpt_contact_agreement ADD PRIMARY KEY (visit_id, agreement_id)');
        $this->addSql('DROP INDEX idx_b560ce0824890b2b ON wpt_contact_agreement');
        $this->addSql('CREATE INDEX IDX_AFFBB6224890B2B ON wpt_contact_agreement (agreement_id)');
        $this->addSql('ALTER TABLE wpt_contact_agreement ADD CONSTRAINT FK_B560CE0824890B2B FOREIGN KEY (agreement_id) REFERENCES wpt_agreement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wpt_contact_student_enrollment DROP FOREIGN KEY FK_DC95389BE7A1254A');
        $this->addSql('DROP INDEX IDX_DC95389BE7A1254A ON wpt_contact_student_enrollment');
        $this->addSql('ALTER TABLE wpt_contact_student_enrollment DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wpt_contact_student_enrollment DROP FOREIGN KEY FK_DC95389BDAE14AC5');
        $this->addSql('ALTER TABLE wpt_contact_student_enrollment CHANGE contact_id visit_id INT NOT NULL');
        $this->addSql('ALTER TABLE wpt_contact_student_enrollment ADD CONSTRAINT FK_3740B2075FA0FF2 FOREIGN KEY (visit_id) REFERENCES wpt_contact (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_3740B2075FA0FF2 ON wpt_contact_student_enrollment (visit_id)');
        $this->addSql('ALTER TABLE wpt_contact_student_enrollment ADD PRIMARY KEY (visit_id, student_enrollment_id)');
        $this->addSql('DROP INDEX idx_dc95389bdae14ac5 ON wpt_contact_student_enrollment');
        $this->addSql('CREATE INDEX IDX_3740B20DAE14AC5 ON wpt_contact_student_enrollment (student_enrollment_id)');
        $this->addSql('ALTER TABLE wpt_contact_student_enrollment ADD CONSTRAINT FK_DC95389BDAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id) ON DELETE CASCADE');
    }
}
