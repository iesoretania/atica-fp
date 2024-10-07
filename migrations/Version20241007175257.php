<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241007175257 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_work_day DROP FOREIGN KEY FK_219B1BDD2F5D0654');
        $this->addSql('CREATE TABLE itp_student_program (id INT AUTO_INCREMENT NOT NULL, program_group_id INT NOT NULL, student_enrollment_id INT NOT NULL, workcenter_id INT NOT NULL, modality INT NOT NULL, authorization_needed TINYINT(1) NOT NULL, authorization_description LONGTEXT DEFAULT NULL, adaptation_needed TINYINT(1) NOT NULL, adaptation_description LONGTEXT DEFAULT NULL, INDEX IDX_27082D767F612572 (program_group_id), INDEX IDX_27082D76DAE14AC5 (student_enrollment_id), INDEX IDX_27082D76A2473C4B (workcenter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_student_program_audit (id INT NOT NULL, rev INT NOT NULL, program_group_id INT DEFAULT NULL, student_enrollment_id INT DEFAULT NULL, workcenter_id INT DEFAULT NULL, modality INT DEFAULT NULL, authorization_needed TINYINT(1) DEFAULT NULL, authorization_description LONGTEXT DEFAULT NULL, adaptation_needed TINYINT(1) DEFAULT NULL, adaptation_description LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_1a3f5deeb15ff9811fc18707d72c2c8f_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE itp_student_program ADD CONSTRAINT FK_27082D767F612572 FOREIGN KEY (program_group_id) REFERENCES itp_program_group (id)');
        $this->addSql('ALTER TABLE itp_student_program ADD CONSTRAINT FK_27082D76DAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id)');
        $this->addSql('ALTER TABLE itp_student_program ADD CONSTRAINT FK_27082D76A2473C4B FOREIGN KEY (workcenter_id) REFERENCES workcenter (id)');
        $this->addSql('ALTER TABLE itp_student_program_audit ADD CONSTRAINT rev_1a3f5deeb15ff9811fc18707d72c2c8f_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_student_learning_program DROP FOREIGN KEY FK_3133C013DAE14AC5');
        $this->addSql('ALTER TABLE itp_student_learning_program DROP FOREIGN KEY FK_3133C013A2473C4B');
        $this->addSql('ALTER TABLE itp_student_learning_program DROP FOREIGN KEY FK_3133C013CEDF9BEF');
        $this->addSql('ALTER TABLE itp_student_learning_program_audit DROP FOREIGN KEY rev_daf610663b684ee243e430f78e762dce_fk');
        $this->addSql('DROP TABLE itp_student_learning_program');
        $this->addSql('DROP TABLE itp_student_learning_program_audit');
        $this->addSql('ALTER TABLE itp_work_day ADD CONSTRAINT FK_219B1BDD2F5D0654 FOREIGN KEY (student_learning_program_id) REFERENCES itp_student_program (id)');
        $this->addSql('CREATE TABLE itp_student_program_activity (id INT AUTO_INCREMENT NOT NULL, activity_id INT NOT NULL, scale_value_id INT DEFAULT NULL, valued_by_id INT DEFAULT NULL, locked TINYINT(1) NOT NULL, details LONGTEXT DEFAULT NULL, INDEX IDX_4A38FA0B81C06096 (activity_id), INDEX IDX_4A38FA0B1B925809 (scale_value_id), INDEX IDX_4A38FA0B26F862F5 (valued_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_student_program_activity_audit (id INT NOT NULL, rev INT NOT NULL, activity_id INT DEFAULT NULL, scale_value_id INT DEFAULT NULL, valued_by_id INT DEFAULT NULL, locked TINYINT(1) DEFAULT NULL, details LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_373d05650e92baa01241f5a138406a9a_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_student_program_activity_comment (id INT AUTO_INCREMENT NOT NULL, student_program_activity_id INT NOT NULL, INDEX IDX_87320FF88F13F316 (student_program_activity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_student_program_activity_comment_audit (id INT NOT NULL, rev INT NOT NULL, student_program_activity_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_a75c4b8562930cb30f63055bce656f74_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE itp_student_program_activity ADD CONSTRAINT FK_4A38FA0B81C06096 FOREIGN KEY (activity_id) REFERENCES itp_activity (id)');
        $this->addSql('ALTER TABLE itp_student_program_activity ADD CONSTRAINT FK_4A38FA0B1B925809 FOREIGN KEY (scale_value_id) REFERENCES edu_performance_scale_value (id)');
        $this->addSql('ALTER TABLE itp_student_program_activity ADD CONSTRAINT FK_4A38FA0B26F862F5 FOREIGN KEY (valued_by_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE itp_student_program_activity_audit ADD CONSTRAINT rev_373d05650e92baa01241f5a138406a9a_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_student_program_activity_comment ADD CONSTRAINT FK_87320FF88F13F316 FOREIGN KEY (student_program_activity_id) REFERENCES itp_student_program_activity (id)');
        $this->addSql('ALTER TABLE itp_student_program_activity_comment_audit ADD CONSTRAINT rev_a75c4b8562930cb30f63055bce656f74_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_student_learning_program_activity DROP FOREIGN KEY FK_3BF21E7E81C06096');
        $this->addSql('ALTER TABLE itp_student_learning_program_activity DROP FOREIGN KEY FK_3BF21E7E1B925809');
        $this->addSql('ALTER TABLE itp_student_learning_program_activity DROP FOREIGN KEY FK_3BF21E7E26F862F5');
        $this->addSql('ALTER TABLE itp_student_learning_program_activity_audit DROP FOREIGN KEY rev_2726065f53799f1a554969312ee235b6_fk');
        $this->addSql('ALTER TABLE itp_student_learning_program_activity_comment DROP FOREIGN KEY FK_E0ACE6A5A2F2EF0A');
        $this->addSql('ALTER TABLE itp_student_learning_program_activity_comment_audit DROP FOREIGN KEY rev_799dee7c9ff3997ea4c15f1c1f8909f1_fk');
        $this->addSql('DROP TABLE itp_student_learning_program_activity');
        $this->addSql('DROP TABLE itp_student_learning_program_activity_audit');
        $this->addSql('DROP TABLE itp_student_learning_program_activity_comment');
        $this->addSql('DROP TABLE itp_student_learning_program_activity_comment_audit');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE itp_student_learning_program_activity (id INT AUTO_INCREMENT NOT NULL, activity_id INT NOT NULL, scale_value_id INT DEFAULT NULL, valued_by_id INT DEFAULT NULL, locked TINYINT(1) NOT NULL, details LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_3BF21E7E81C06096 (activity_id), INDEX IDX_3BF21E7E1B925809 (scale_value_id), INDEX IDX_3BF21E7E26F862F5 (valued_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE itp_student_learning_program_activity_audit (id INT NOT NULL, rev INT NOT NULL, activity_id INT DEFAULT NULL, scale_value_id INT DEFAULT NULL, valued_by_id INT DEFAULT NULL, locked TINYINT(1) DEFAULT NULL, details LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_2726065f53799f1a554969312ee235b6_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE itp_student_learning_program_activity_comment (id INT AUTO_INCREMENT NOT NULL, student_learning_program_activity_id INT NOT NULL, INDEX IDX_E0ACE6A5A2F2EF0A (student_learning_program_activity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE itp_student_learning_program_activity_comment_audit (id INT NOT NULL, rev INT NOT NULL, student_learning_program_activity_id INT DEFAULT NULL, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_799dee7c9ff3997ea4c15f1c1f8909f1_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE itp_student_learning_program_activity ADD CONSTRAINT FK_3BF21E7E81C06096 FOREIGN KEY (activity_id) REFERENCES itp_activity (id)');
        $this->addSql('ALTER TABLE itp_student_learning_program_activity ADD CONSTRAINT FK_3BF21E7E1B925809 FOREIGN KEY (scale_value_id) REFERENCES edu_performance_scale_value (id)');
        $this->addSql('ALTER TABLE itp_student_learning_program_activity ADD CONSTRAINT FK_3BF21E7E26F862F5 FOREIGN KEY (valued_by_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE itp_student_learning_program_activity_audit ADD CONSTRAINT rev_2726065f53799f1a554969312ee235b6_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_student_learning_program_activity_comment ADD CONSTRAINT FK_E0ACE6A5A2F2EF0A FOREIGN KEY (student_learning_program_activity_id) REFERENCES itp_student_learning_program_activity (id)');
        $this->addSql('ALTER TABLE itp_student_learning_program_activity_comment_audit ADD CONSTRAINT rev_799dee7c9ff3997ea4c15f1c1f8909f1_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_student_program_activity DROP FOREIGN KEY FK_4A38FA0B81C06096');
        $this->addSql('ALTER TABLE itp_student_program_activity DROP FOREIGN KEY FK_4A38FA0B1B925809');
        $this->addSql('ALTER TABLE itp_student_program_activity DROP FOREIGN KEY FK_4A38FA0B26F862F5');
        $this->addSql('ALTER TABLE itp_student_program_activity_audit DROP FOREIGN KEY rev_373d05650e92baa01241f5a138406a9a_fk');
        $this->addSql('ALTER TABLE itp_student_program_activity_comment DROP FOREIGN KEY FK_87320FF88F13F316');
        $this->addSql('ALTER TABLE itp_student_program_activity_comment_audit DROP FOREIGN KEY rev_a75c4b8562930cb30f63055bce656f74_fk');
        $this->addSql('DROP TABLE itp_student_program_activity');
        $this->addSql('DROP TABLE itp_student_program_activity_audit');
        $this->addSql('DROP TABLE itp_student_program_activity_comment');
        $this->addSql('DROP TABLE itp_student_program_activity_comment_audit');
        $this->addSql('ALTER TABLE itp_work_day DROP FOREIGN KEY FK_219B1BDD2F5D0654');
        $this->addSql('CREATE TABLE itp_student_learning_program (id INT AUTO_INCREMENT NOT NULL, training_program_group_id INT NOT NULL, student_enrollment_id INT NOT NULL, workcenter_id INT NOT NULL, modality INT NOT NULL, authorization_needed TINYINT(1) NOT NULL, authorization_description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, adaptation_needed TINYINT(1) NOT NULL, adaptation_description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_3133C013CEDF9BEF (training_program_group_id), INDEX IDX_3133C013A2473C4B (workcenter_id), INDEX IDX_3133C013DAE14AC5 (student_enrollment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE itp_student_learning_program_audit (id INT NOT NULL, rev INT NOT NULL, training_program_group_id INT DEFAULT NULL, student_enrollment_id INT DEFAULT NULL, workcenter_id INT DEFAULT NULL, modality INT DEFAULT NULL, authorization_needed TINYINT(1) DEFAULT NULL, authorization_description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, adaptation_needed TINYINT(1) DEFAULT NULL, adaptation_description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_daf610663b684ee243e430f78e762dce_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE itp_student_learning_program ADD CONSTRAINT FK_3133C013DAE14AC5 FOREIGN KEY (student_enrollment_id) REFERENCES edu_student_enrollment (id)');
        $this->addSql('ALTER TABLE itp_student_learning_program ADD CONSTRAINT FK_3133C013A2473C4B FOREIGN KEY (workcenter_id) REFERENCES workcenter (id)');
        $this->addSql('ALTER TABLE itp_student_learning_program ADD CONSTRAINT FK_3133C013CEDF9BEF FOREIGN KEY (training_program_group_id) REFERENCES itp_program_group (id)');
        $this->addSql('ALTER TABLE itp_student_learning_program_audit ADD CONSTRAINT rev_daf610663b684ee243e430f78e762dce_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE itp_student_program DROP FOREIGN KEY FK_27082D767F612572');
        $this->addSql('ALTER TABLE itp_student_program DROP FOREIGN KEY FK_27082D76DAE14AC5');
        $this->addSql('ALTER TABLE itp_student_program DROP FOREIGN KEY FK_27082D76A2473C4B');
        $this->addSql('ALTER TABLE itp_student_program_audit DROP FOREIGN KEY rev_1a3f5deeb15ff9811fc18707d72c2c8f_fk');
        $this->addSql('DROP TABLE itp_student_program');
        $this->addSql('DROP TABLE itp_student_program_audit');
        $this->addSql('ALTER TABLE itp_work_day ADD CONSTRAINT FK_219B1BDD2F5D0654 FOREIGN KEY (student_learning_program_id) REFERENCES itp_student_learning_program (id)');
    }
}
