<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200306225637 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wpt_agreement_enrollment ADD work_tutor_id INT NOT NULL, ADD educational_tutor_id INT NOT NULL, ADD company_survey_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wpt_agreement_enrollment ADD CONSTRAINT FK_A24B4F78F53AEEAD FOREIGN KEY (work_tutor_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE wpt_agreement_enrollment ADD CONSTRAINT FK_A24B4F78E7F72E80 FOREIGN KEY (educational_tutor_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE wpt_agreement_enrollment ADD CONSTRAINT FK_A24B4F7880E5DA6D FOREIGN KEY (company_survey_id) REFERENCES answered_survey (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_A24B4F78F53AEEAD ON wpt_agreement_enrollment (work_tutor_id)');
        $this->addSql('CREATE INDEX IDX_A24B4F78E7F72E80 ON wpt_agreement_enrollment (educational_tutor_id)');
        $this->addSql('CREATE INDEX IDX_A24B4F7880E5DA6D ON wpt_agreement_enrollment (company_survey_id)');
        $this->addSql('ALTER TABLE wpt_agreement_activity DROP FOREIGN KEY FK_889D477F24890B2B');
        $this->addSql('DROP INDEX IDX_889D477F24890B2B ON wpt_agreement_activity');
        $this->addSql('ALTER TABLE wpt_agreement_activity DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wpt_agreement_activity CHANGE agreement_id agreement_enrollment_id INT NOT NULL');
        $this->addSql('ALTER TABLE wpt_agreement_activity ADD CONSTRAINT FK_889D477FA32BEB28 FOREIGN KEY (agreement_enrollment_id) REFERENCES wpt_agreement_enrollment (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_889D477FA32BEB28 ON wpt_agreement_activity (agreement_enrollment_id)');
        $this->addSql('ALTER TABLE wpt_agreement_activity ADD PRIMARY KEY (agreement_enrollment_id, activity_id)');
        $this->addSql('ALTER TABLE wpt_work_day DROP start_time1, DROP end_time1, DROP start_time2, DROP end_time2');
        $this->addSql('ALTER TABLE wpt_work_day_audit DROP start_time1, DROP end_time1, DROP start_time2, DROP end_time2');
        $this->addSql('ALTER TABLE wpt_agreement DROP FOREIGN KEY FK_22310F9480E5DA6D');
        $this->addSql('ALTER TABLE wpt_agreement DROP FOREIGN KEY FK_22310F94E7F72E80');
        $this->addSql('ALTER TABLE wpt_agreement DROP FOREIGN KEY FK_22310F94F53AEEAD');
        $this->addSql('DROP INDEX IDX_22310F94F53AEEAD ON wpt_agreement');
        $this->addSql('DROP INDEX IDX_22310F94E7F72E80 ON wpt_agreement');
        $this->addSql('DROP INDEX IDX_22310F9480E5DA6D ON wpt_agreement');
        $this->addSql('ALTER TABLE wpt_agreement DROP company_survey_id, DROP educational_tutor_id, DROP work_tutor_id');
        $this->addSql('ALTER TABLE wpt_agreement_audit DROP work_tutor_id, DROP educational_tutor_id, DROP company_survey_id');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wpt_agreement ADD company_survey_id INT DEFAULT NULL, ADD educational_tutor_id INT NOT NULL, ADD work_tutor_id INT NOT NULL');
        $this->addSql('ALTER TABLE wpt_agreement ADD CONSTRAINT FK_22310F9480E5DA6D FOREIGN KEY (company_survey_id) REFERENCES answered_survey (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE wpt_agreement ADD CONSTRAINT FK_22310F94E7F72E80 FOREIGN KEY (educational_tutor_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE wpt_agreement ADD CONSTRAINT FK_22310F94F53AEEAD FOREIGN KEY (work_tutor_id) REFERENCES person (id)');
        $this->addSql('CREATE INDEX IDX_22310F94F53AEEAD ON wpt_agreement (work_tutor_id)');
        $this->addSql('CREATE INDEX IDX_22310F94E7F72E80 ON wpt_agreement (educational_tutor_id)');
        $this->addSql('CREATE INDEX IDX_22310F9480E5DA6D ON wpt_agreement (company_survey_id)');
        $this->addSql('ALTER TABLE wpt_agreement_activity DROP FOREIGN KEY FK_889D477FA32BEB28');
        $this->addSql('DROP INDEX IDX_889D477FA32BEB28 ON wpt_agreement_activity');
        $this->addSql('ALTER TABLE wpt_agreement_activity DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wpt_agreement_activity CHANGE agreement_enrollment_id agreement_id INT NOT NULL');
        $this->addSql('ALTER TABLE wpt_agreement_activity ADD CONSTRAINT FK_889D477F24890B2B FOREIGN KEY (agreement_id) REFERENCES wpt_agreement (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_889D477F24890B2B ON wpt_agreement_activity (agreement_id)');
        $this->addSql('ALTER TABLE wpt_agreement_activity ADD PRIMARY KEY (agreement_id, activity_id)');
        $this->addSql('ALTER TABLE wpt_agreement_audit ADD work_tutor_id INT DEFAULT NULL, ADD educational_tutor_id INT DEFAULT NULL, ADD company_survey_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wpt_agreement_enrollment DROP FOREIGN KEY FK_A24B4F78F53AEEAD');
        $this->addSql('ALTER TABLE wpt_agreement_enrollment DROP FOREIGN KEY FK_A24B4F78E7F72E80');
        $this->addSql('ALTER TABLE wpt_agreement_enrollment DROP FOREIGN KEY FK_A24B4F7880E5DA6D');
        $this->addSql('DROP INDEX IDX_A24B4F78F53AEEAD ON wpt_agreement_enrollment');
        $this->addSql('DROP INDEX IDX_A24B4F78E7F72E80 ON wpt_agreement_enrollment');
        $this->addSql('DROP INDEX IDX_A24B4F7880E5DA6D ON wpt_agreement_enrollment');
        $this->addSql('ALTER TABLE wpt_agreement_enrollment DROP work_tutor_id, DROP educational_tutor_id, DROP company_survey_id');
        $this->addSql('ALTER TABLE wpt_work_day ADD start_time1 VARCHAR(5) DEFAULT NULL COLLATE utf8mb4_spanish_ci, ADD end_time1 VARCHAR(5) DEFAULT NULL COLLATE utf8mb4_spanish_ci, ADD start_time2 VARCHAR(5) DEFAULT NULL COLLATE utf8mb4_spanish_ci, ADD end_time2 VARCHAR(5) DEFAULT NULL COLLATE utf8mb4_spanish_ci');
        $this->addSql('ALTER TABLE wpt_work_day_audit ADD start_time1 VARCHAR(5) DEFAULT NULL COLLATE utf8mb4_spanish_ci, ADD end_time1 VARCHAR(5) DEFAULT NULL COLLATE utf8mb4_spanish_ci, ADD start_time2 VARCHAR(5) DEFAULT NULL COLLATE utf8mb4_spanish_ci, ADD end_time2 VARCHAR(5) DEFAULT NULL COLLATE utf8mb4_spanish_ci');
    }
}
