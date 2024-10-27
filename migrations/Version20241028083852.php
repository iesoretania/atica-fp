<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241028083852 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE itp_student_program_workcenter_activity (id INT AUTO_INCREMENT NOT NULL, activity_id INT NOT NULL, student_program_workcenter_id INT NOT NULL, scale_value_id INT DEFAULT NULL, valued_by_id INT DEFAULT NULL, disabled TINYINT(1) NOT NULL, details LONGTEXT DEFAULT NULL, INDEX IDX_2AEA0BDF81C06096 (activity_id), INDEX IDX_2AEA0BDFF0C2682D (student_program_workcenter_id), INDEX IDX_2AEA0BDF1B925809 (scale_value_id), INDEX IDX_2AEA0BDF26F862F5 (valued_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_student_program_workcenter_activity_audit (id INT NOT NULL, rev INT NOT NULL, activity_id INT DEFAULT NULL, student_program_workcenter_id INT DEFAULT NULL, scale_value_id INT DEFAULT NULL, valued_by_id INT DEFAULT NULL, disabled TINYINT(1) DEFAULT NULL, details LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_50184693a2db8bf225d8ecb9dcdf64e2_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_student_program_workcenter_activity_comment (id INT AUTO_INCREMENT NOT NULL, student_program_activity_id INT NOT NULL, INDEX IDX_1A88C1A8F13F316 (student_program_activity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_student_program_workcenter_activity_comment_audit (id INT NOT NULL, rev INT NOT NULL, student_program_activity_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_7da87a914b245b80a9e64d313a43ea86_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity ADD CONSTRAINT FK_2AEA0BDF81C06096 FOREIGN KEY (activity_id) REFERENCES itp_activity (id)');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity ADD CONSTRAINT FK_2AEA0BDFF0C2682D FOREIGN KEY (student_program_workcenter_id) REFERENCES itp_student_program_workcenter (id)');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity ADD CONSTRAINT FK_2AEA0BDF1B925809 FOREIGN KEY (scale_value_id) REFERENCES edu_performance_scale_value (id)');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity ADD CONSTRAINT FK_2AEA0BDF26F862F5 FOREIGN KEY (valued_by_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity_audit ADD CONSTRAINT rev_50184693a2db8bf225d8ecb9dcdf64e2_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity_comment ADD CONSTRAINT FK_1A88C1A8F13F316 FOREIGN KEY (student_program_activity_id) REFERENCES itp_student_program_workcenter_activity (id)');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity_comment_audit ADD CONSTRAINT rev_7da87a914b245b80a9e64d313a43ea86_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_student_program_activity DROP FOREIGN KEY FK_4A38FA0B1B925809');
        $this->addSql('ALTER TABLE itp_student_program_activity DROP FOREIGN KEY FK_4A38FA0B26F862F5');
        $this->addSql('ALTER TABLE itp_student_program_activity DROP FOREIGN KEY FK_4A38FA0B81C06096');
        $this->addSql('ALTER TABLE itp_student_program_activity DROP FOREIGN KEY FK_4A38FA0BF0C2682D');
        $this->addSql('ALTER TABLE itp_student_program_activity_audit DROP FOREIGN KEY rev_373d05650e92baa01241f5a138406a9a_fk');
        $this->addSql('ALTER TABLE itp_student_program_activity_comment DROP FOREIGN KEY FK_87320FF88F13F316');
        $this->addSql('ALTER TABLE itp_student_program_activity_comment_audit DROP FOREIGN KEY rev_a75c4b8562930cb30f63055bce656f74_fk');
        $this->addSql('DROP TABLE itp_student_program_activity');
        $this->addSql('DROP TABLE itp_student_program_activity_audit');
        $this->addSql('DROP TABLE itp_student_program_activity_comment');
        $this->addSql('DROP TABLE itp_student_program_activity_comment_audit');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE itp_student_program_activity (id INT AUTO_INCREMENT NOT NULL, activity_id INT NOT NULL, scale_value_id INT DEFAULT NULL, valued_by_id INT DEFAULT NULL, student_program_workcenter_id INT NOT NULL, locked TINYINT(1) NOT NULL, details LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_4A38FA0B81C06096 (activity_id), INDEX IDX_4A38FA0B1B925809 (scale_value_id), INDEX IDX_4A38FA0B26F862F5 (valued_by_id), INDEX IDX_4A38FA0BF0C2682D (student_program_workcenter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE itp_student_program_activity_audit (id INT NOT NULL, rev INT NOT NULL, activity_id INT DEFAULT NULL, scale_value_id INT DEFAULT NULL, valued_by_id INT DEFAULT NULL, locked TINYINT(1) DEFAULT NULL, details LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, student_program_workcenter_id INT DEFAULT NULL, INDEX rev_373d05650e92baa01241f5a138406a9a_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE itp_student_program_activity_comment (id INT AUTO_INCREMENT NOT NULL, student_program_activity_id INT NOT NULL, INDEX IDX_87320FF88F13F316 (student_program_activity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE itp_student_program_activity_comment_audit (id INT NOT NULL, rev INT NOT NULL, student_program_activity_id INT DEFAULT NULL, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_a75c4b8562930cb30f63055bce656f74_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE itp_student_program_activity ADD CONSTRAINT FK_4A38FA0B1B925809 FOREIGN KEY (scale_value_id) REFERENCES edu_performance_scale_value (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE itp_student_program_activity ADD CONSTRAINT FK_4A38FA0B26F862F5 FOREIGN KEY (valued_by_id) REFERENCES person (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE itp_student_program_activity ADD CONSTRAINT FK_4A38FA0B81C06096 FOREIGN KEY (activity_id) REFERENCES itp_activity (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE itp_student_program_activity ADD CONSTRAINT FK_4A38FA0BF0C2682D FOREIGN KEY (student_program_workcenter_id) REFERENCES itp_student_program_workcenter (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE itp_student_program_activity_audit ADD CONSTRAINT rev_373d05650e92baa01241f5a138406a9a_fk FOREIGN KEY (rev) REFERENCES revisions (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE itp_student_program_activity_comment ADD CONSTRAINT FK_87320FF88F13F316 FOREIGN KEY (student_program_activity_id) REFERENCES itp_student_program_activity (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE itp_student_program_activity_comment_audit ADD CONSTRAINT rev_a75c4b8562930cb30f63055bce656f74_fk FOREIGN KEY (rev) REFERENCES revisions (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity DROP FOREIGN KEY FK_2AEA0BDF81C06096');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity DROP FOREIGN KEY FK_2AEA0BDFF0C2682D');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity DROP FOREIGN KEY FK_2AEA0BDF1B925809');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity DROP FOREIGN KEY FK_2AEA0BDF26F862F5');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity_audit DROP FOREIGN KEY rev_50184693a2db8bf225d8ecb9dcdf64e2_fk');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity_comment DROP FOREIGN KEY FK_1A88C1A8F13F316');
        $this->addSql('ALTER TABLE itp_student_program_workcenter_activity_comment_audit DROP FOREIGN KEY rev_7da87a914b245b80a9e64d313a43ea86_fk');
        $this->addSql('DROP TABLE itp_student_program_workcenter_activity');
        $this->addSql('DROP TABLE itp_student_program_workcenter_activity_audit');
        $this->addSql('DROP TABLE itp_student_program_workcenter_activity_comment');
        $this->addSql('DROP TABLE itp_student_program_workcenter_activity_comment_audit');
    }
}
