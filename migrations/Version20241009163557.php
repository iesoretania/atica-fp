<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241009163557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE itp_student_program_workcenter (id INT AUTO_INCREMENT NOT NULL, student_program_id INT NOT NULL, workcenter_id INT NOT NULL, INDEX IDX_65275568D367EAEB (student_program_id), INDEX IDX_65275568A2473C4B (workcenter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_student_program_workcenter_audit (id INT NOT NULL, rev INT NOT NULL, student_program_id INT DEFAULT NULL, workcenter_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_06d3662eda53d40081d2087a87118e04_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE itp_student_program_workcenter ADD CONSTRAINT FK_65275568D367EAEB FOREIGN KEY (student_program_id) REFERENCES itp_student_program (id)');
        $this->addSql('ALTER TABLE itp_student_program_workcenter ADD CONSTRAINT FK_65275568A2473C4B FOREIGN KEY (workcenter_id) REFERENCES workcenter (id)');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_audit ADD CONSTRAINT rev_06d3662eda53d40081d2087a87118e04_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_student_program DROP FOREIGN KEY FK_27082D76A2473C4B');
        $this->addSql('DROP INDEX IDX_27082D76A2473C4B ON itp_student_program');
        $this->addSql('ALTER TABLE itp_student_program DROP workcenter_id');
        $this->addSql('ALTER TABLE itp_student_program_audit DROP workcenter_id');
        $this->addSql('ALTER TABLE itp_work_day DROP FOREIGN KEY FK_219B1BDD2F5D0654');
        $this->addSql('DROP INDEX IDX_219B1BDD2F5D0654 ON itp_work_day');
        $this->addSql('ALTER TABLE itp_work_day CHANGE student_learning_program_id student_program_workcenter_id INT NOT NULL');
        $this->addSql('ALTER TABLE itp_work_day ADD CONSTRAINT FK_219B1BDDF0C2682D FOREIGN KEY (student_program_workcenter_id) REFERENCES itp_student_program_workcenter (id)');
        $this->addSql('CREATE INDEX IDX_219B1BDDF0C2682D ON itp_work_day (student_program_workcenter_id)');
        $this->addSql('ALTER TABLE itp_work_day_audit CHANGE student_learning_program_id student_program_workcenter_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_work_day DROP FOREIGN KEY FK_219B1BDDF0C2682D');
        $this->addSql('ALTER TABLE itp_student_program_workcenter DROP FOREIGN KEY FK_65275568D367EAEB');
        $this->addSql('ALTER TABLE itp_student_program_workcenter DROP FOREIGN KEY FK_65275568A2473C4B');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_audit DROP FOREIGN KEY rev_06d3662eda53d40081d2087a87118e04_fk');
        $this->addSql('DROP TABLE itp_student_program_workcenter');
        $this->addSql('DROP TABLE itp_student_program_workcenter_audit');
        $this->addSql('ALTER TABLE itp_student_program ADD workcenter_id INT NOT NULL');
        $this->addSql('ALTER TABLE itp_student_program ADD CONSTRAINT FK_27082D76A2473C4B FOREIGN KEY (workcenter_id) REFERENCES workcenter (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_27082D76A2473C4B ON itp_student_program (workcenter_id)');
        $this->addSql('ALTER TABLE itp_student_program_audit ADD workcenter_id INT DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_219B1BDDF0C2682D ON itp_work_day');
        $this->addSql('ALTER TABLE itp_work_day CHANGE student_program_workcenter_id student_learning_program_id INT NOT NULL');
        $this->addSql('ALTER TABLE itp_work_day ADD CONSTRAINT FK_219B1BDD2F5D0654 FOREIGN KEY (student_learning_program_id) REFERENCES itp_student_program (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_219B1BDD2F5D0654 ON itp_work_day (student_learning_program_id)');
        $this->addSql('ALTER TABLE itp_work_day_audit CHANGE student_program_workcenter_id student_learning_program_id INT DEFAULT NULL');
    }
}
