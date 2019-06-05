<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190606175732 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE survey (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, title VARCHAR(255) NOT NULL, start_timestamp DATETIME DEFAULT NULL, end_timestamp DATETIME DEFAULT NULL, INDEX IDX_AD5F9BFC32C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE survey_audit (id INT NOT NULL, rev INT NOT NULL, organization_id INT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, start_timestamp DATETIME DEFAULT NULL, end_timestamp DATETIME DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_0b043444544b35c998515597d9c72406_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE survey_question (id INT AUTO_INCREMENT NOT NULL, survey_id INT NOT NULL, description LONGTEXT NOT NULL, type VARCHAR(255) NOT NULL, mandatory TINYINT(1) NOT NULL, order_nr INT NOT NULL, INDEX IDX_EA000F69B3FE509D (survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE survey_question_audit (id INT NOT NULL, rev INT NOT NULL, survey_id INT DEFAULT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, mandatory TINYINT(1) DEFAULT NULL, order_nr INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_c3e747f7590f2b4f4a7532c099a72b37_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE answered_survey (id INT AUTO_INCREMENT NOT NULL, survey_id INT DEFAULT NULL, timestamp DATETIME NOT NULL, INDEX IDX_64D69900B3FE509D (survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE answered_survey_audit (id INT NOT NULL, rev INT NOT NULL, survey_id INT DEFAULT NULL, timestamp DATETIME DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_b49bd09be566418816c3b13bd208621c_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE answered_survey_question (id INT AUTO_INCREMENT NOT NULL, answered_survey_id INT NOT NULL, survey_question_id INT NOT NULL, text_value LONGTEXT DEFAULT NULL, numeric_value INT DEFAULT NULL, INDEX IDX_F38ED8C7A97283E6 (answered_survey_id), INDEX IDX_F38ED8C7A6DF29BA (survey_question_id), UNIQUE INDEX UNIQ_F38ED8C7A6DF29BAA97283E6 (survey_question_id, answered_survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE answered_survey_question_audit (id INT NOT NULL, rev INT NOT NULL, answered_survey_id INT DEFAULT NULL, survey_question_id INT DEFAULT NULL, text_value LONGTEXT DEFAULT NULL, numeric_value INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_2e8f92797ef775558ce93daa1c47f1fd_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_teacher_survey (id INT AUTO_INCREMENT NOT NULL, teacher_id INT NOT NULL, answered_survey_id INT NOT NULL, INDEX IDX_118986C141807E1D (teacher_id), INDEX IDX_118986C1A97283E6 (answered_survey_id), UNIQUE INDEX UNIQ_118986C141807E1DA97283E6 (teacher_id, answered_survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_teacher_survey_audit (id INT NOT NULL, rev INT NOT NULL, teacher_id INT DEFAULT NULL, answered_survey_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_75c9176e5fb788ba65cb46932d654fed_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE survey ADD CONSTRAINT FK_AD5F9BFC32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE survey_question ADD CONSTRAINT FK_EA000F69B3FE509D FOREIGN KEY (survey_id) REFERENCES survey (id)');
        $this->addSql('ALTER TABLE answered_survey ADD CONSTRAINT FK_64D69900B3FE509D FOREIGN KEY (survey_id) REFERENCES survey (id)');
        $this->addSql('ALTER TABLE answered_survey_question ADD CONSTRAINT FK_F38ED8C7A97283E6 FOREIGN KEY (answered_survey_id) REFERENCES answered_survey (id)');
        $this->addSql('ALTER TABLE answered_survey_question ADD CONSTRAINT FK_F38ED8C7A6DF29BA FOREIGN KEY (survey_question_id) REFERENCES survey_question (id)');
        $this->addSql('ALTER TABLE wlt_teacher_survey ADD CONSTRAINT FK_118986C141807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE wlt_teacher_survey ADD CONSTRAINT FK_118986C1A97283E6 FOREIGN KEY (answered_survey_id) REFERENCES answered_survey (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE edu_training ADD wlt_student_survey_id INT DEFAULT NULL, ADD wlt_company_survey_id INT DEFAULT NULL, ADD wlt_teacher_survey_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE edu_training ADD CONSTRAINT FK_2692AD501294B084 FOREIGN KEY (wlt_student_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE edu_training ADD CONSTRAINT FK_2692AD5046E1FBF4 FOREIGN KEY (wlt_company_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE edu_training ADD CONSTRAINT FK_2692AD50F913D1F FOREIGN KEY (wlt_teacher_survey_id) REFERENCES survey (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_2692AD501294B084 ON edu_training (wlt_student_survey_id)');
        $this->addSql('CREATE INDEX IDX_2692AD5046E1FBF4 ON edu_training (wlt_company_survey_id)');
        $this->addSql('CREATE INDEX IDX_2692AD50F913D1F ON edu_training (wlt_teacher_survey_id)');
        $this->addSql('ALTER TABLE wlt_agreement ADD student_survey_id INT DEFAULT NULL, ADD company_survey_id INT DEFAULT NULL, DROP student_poll_submitted');
        $this->addSql('ALTER TABLE wlt_agreement ADD CONSTRAINT FK_2B23AFE9D490911D FOREIGN KEY (student_survey_id) REFERENCES answered_survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE wlt_agreement ADD CONSTRAINT FK_2B23AFE980E5DA6D FOREIGN KEY (company_survey_id) REFERENCES answered_survey (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_2B23AFE9D490911D ON wlt_agreement (student_survey_id)');
        $this->addSql('CREATE INDEX IDX_2B23AFE980E5DA6D ON wlt_agreement (company_survey_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edu_training DROP FOREIGN KEY FK_2692AD501294B084');
        $this->addSql('ALTER TABLE edu_training DROP FOREIGN KEY FK_2692AD5046E1FBF4');
        $this->addSql('ALTER TABLE edu_training DROP FOREIGN KEY FK_2692AD50F913D1F');
        $this->addSql('ALTER TABLE survey_question DROP FOREIGN KEY FK_EA000F69B3FE509D');
        $this->addSql('ALTER TABLE answered_survey DROP FOREIGN KEY FK_64D69900B3FE509D');
        $this->addSql('ALTER TABLE answered_survey_question DROP FOREIGN KEY FK_F38ED8C7A6DF29BA');
        $this->addSql('ALTER TABLE wlt_agreement DROP FOREIGN KEY FK_2B23AFE9D490911D');
        $this->addSql('ALTER TABLE wlt_agreement DROP FOREIGN KEY FK_2B23AFE980E5DA6D');
        $this->addSql('ALTER TABLE answered_survey_question DROP FOREIGN KEY FK_F38ED8C7A97283E6');
        $this->addSql('ALTER TABLE wlt_teacher_survey DROP FOREIGN KEY FK_118986C1A97283E6');
        $this->addSql('DROP TABLE survey');
        $this->addSql('DROP TABLE survey_audit');
        $this->addSql('DROP TABLE survey_question');
        $this->addSql('DROP TABLE survey_question_audit');
        $this->addSql('DROP TABLE answered_survey');
        $this->addSql('DROP TABLE answered_survey_audit');
        $this->addSql('DROP TABLE answered_survey_question');
        $this->addSql('DROP TABLE answered_survey_question_audit');
        $this->addSql('DROP TABLE wlt_teacher_survey');
        $this->addSql('DROP TABLE wlt_teacher_survey_audit');
        $this->addSql('DROP INDEX IDX_2692AD501294B084 ON edu_training');
        $this->addSql('DROP INDEX IDX_2692AD5046E1FBF4 ON edu_training');
        $this->addSql('DROP INDEX IDX_2692AD50F913D1F ON edu_training');
        $this->addSql('ALTER TABLE edu_training DROP wlt_student_survey_id, DROP wlt_company_survey_id, DROP wlt_teacher_survey_id');
        $this->addSql('DROP INDEX IDX_2B23AFE9D490911D ON wlt_agreement');
        $this->addSql('DROP INDEX IDX_2B23AFE980E5DA6D ON wlt_agreement');
        $this->addSql('ALTER TABLE wlt_agreement ADD student_poll_submitted TINYINT(1) NOT NULL, DROP student_survey_id, DROP company_survey_id');
    }
}
