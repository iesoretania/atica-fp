<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240814114503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE wpt_travel_expense SET other_expenses = 0 WHERE other_expenses IS NULL');
        $this->addSql('UPDATE wlt_travel_expense SET other_expenses = 0 WHERE other_expenses IS NULL');
        $this->addSql('CREATE TABLE wlt_contact_project_audit (contact_id INT NOT NULL, project_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_abc3d380c2aa42af58909c1fd4f4dc82_idx (rev), PRIMARY KEY(contact_id, project_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_contact_student_enrollment_audit (contact_id INT NOT NULL, student_enrollment_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_e0bd3edfb4c855c59d87e3bcba7e9825_idx (rev), PRIMARY KEY(contact_id, student_enrollment_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_learning_program_activity_realization_audit (learning_program_id INT NOT NULL, activity_realization_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_ea7fce12514ea7fc1d7a89d6eedaccbf_idx (rev), PRIMARY KEY(learning_program_id, activity_realization_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_meeting_student_enrollment_audit (meeting_id INT NOT NULL, student_enrollment_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_eec9e93b7a9dff9fef2970100c0b2da9_idx (rev), PRIMARY KEY(meeting_id, student_enrollment_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_meeting_teacher_audit (meeting_id INT NOT NULL, teacher_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_188d4938230f9bff872496b972c09981_idx (rev), PRIMARY KEY(meeting_id, teacher_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_project_group_audit (project_id INT NOT NULL, group_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_d9538b953230ccb18da4bab0b4de9237_idx (rev), PRIMARY KEY(project_id, group_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_project_student_enrollment_audit (project_id INT NOT NULL, student_enrollment_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_50632f17d0233de6402b0980b438b5e1_idx (rev), PRIMARY KEY(project_id, student_enrollment_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_travel_expense_agreement_audit (travel_expense_id INT NOT NULL, agreement_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_539576fd942d298158b17757be101662_idx (rev), PRIMARY KEY(travel_expense_id, agreement_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_tracking_audit (work_day_id INT NOT NULL, activity_realization_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_401b520edfe354349e84f8011843482d_idx (rev), PRIMARY KEY(work_day_id, activity_realization_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_activity_criterion_audit (activity_id INT NOT NULL, criterion_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_42b2c0fbadffd3ee42fe5760da2d16a7_idx (rev), PRIMARY KEY(activity_id, criterion_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_contact_agreement_audit (contact_id INT NOT NULL, agreement_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_670f76fa408fe3e42c32679f18c3bbe7_idx (rev), PRIMARY KEY(contact_id, agreement_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_contact_student_enrollment_audit (contact_id INT NOT NULL, student_enrollment_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_676fe97e25939c1e21812b83094a2efa_idx (rev), PRIMARY KEY(contact_id, student_enrollment_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_travel_expense_agreement_audit (travel_expense_id INT NOT NULL, agreement_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_bcc2b7829a2e57f8c109e7da204f5623_idx (rev), PRIMARY KEY(travel_expense_id, agreement_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE answered_survey_audit ADD CONSTRAINT rev_b49bd09be566418816c3b13bd208621c_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE answered_survey_question_audit ADD CONSTRAINT rev_2e8f92797ef775558ce93daa1c47f1fd_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE company_audit ADD CONSTRAINT rev_3af728e1cf16bdb0f83bb90e3b1af48a_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE edu_academic_year_audit ADD CONSTRAINT rev_32b80cc3e6e41d58d2de465d160f9958_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE edu_contact_method_audit ADD CONSTRAINT rev_f0caa067d32baecb08ec4f3fc820d0a6_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE person_audit ADD CONSTRAINT rev_907be00c9c366335b3359c1e8e2f6227_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE role CHANGE person_id person_id INT NOT NULL, CHANGE organization_id organization_id INT NOT NULL');
        $this->addSql('ALTER TABLE role_audit ADD CONSTRAINT rev_92317bf7adb4788531df0b1cb910b5fc_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE survey_audit ADD CONSTRAINT rev_0b043444544b35c998515597d9c72406_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE survey_question_audit ADD CONSTRAINT rev_c3e747f7590f2b4f4a7532c099a72b37_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wlt_activity_realization_grade_audit ADD CONSTRAINT rev_f465a2778ab91cc6186229f95700df6f_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization_audit ADD CONSTRAINT rev_3aa810fb5b23d95bfecc473791749abf_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization_comment CHANGE person_id person_id INT NOT NULL');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization_comment_audit ADD CONSTRAINT rev_935c6eb03972c07b2fc1ea23e44b8f95_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wlt_contact_audit ADD CONSTRAINT rev_1a3b3959a5ced6400bc91d305339a7c0_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey_audit ADD CONSTRAINT rev_68c9c40ff36ffd2b1378aa4b2746dcac_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wlt_learning_program_audit ADD CONSTRAINT rev_3e4a8a47037e8657861e40d29d7511a6_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wlt_meeting_audit ADD CONSTRAINT rev_4f5b29d8966971ed79a052aacd98c68f_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wlt_project_audit ADD CONSTRAINT rev_e55594229ab1a86fcf85548ae1e37a5e_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wlt_student_answered_survey_audit ADD CONSTRAINT rev_7233771bba2039be6047b538a296dbb3_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wlt_travel_expense CHANGE travel_route_id travel_route_id INT NOT NULL, CHANGE other_expenses other_expenses INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE wlt_travel_expense_audit CHANGE other_expenses other_expenses INT DEFAULT 0');
        $this->addSql('ALTER TABLE wlt_travel_expense_audit ADD CONSTRAINT rev_a88868b0c6712e0abd1e296b1217a283_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wlt_work_day_audit ADD CONSTRAINT rev_a182ece7d34180bb9b41759ce4dafebc_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wlt_work_tutor_answered_survey_audit ADD CONSTRAINT rev_583e3ede99c93193ba43436008b164e7_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE workcenter_audit ADD CONSTRAINT rev_54a4a65186f56b9de79ff6f4b726b582_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wpt_activity_audit ADD CONSTRAINT rev_9c2622768c8baa59674e5941fb6223f9_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wpt_agreement_audit ADD CONSTRAINT rev_c44410e99c46f4c316201779be629ca6_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wpt_contact_audit ADD CONSTRAINT rev_14a73b1dc586567879897aac041a9700_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wpt_educational_tutor_answered_survey_audit ADD CONSTRAINT rev_d3a1a7f9086d9bd8a93c67e81b8519eb_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wpt_shift_audit ADD CONSTRAINT rev_71f73a2d4dc70e8d55eaad661689e458_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wpt_student_answered_survey_audit ADD CONSTRAINT rev_18ec14100e728ec30e97808504c4bcae_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wpt_travel_expense CHANGE travel_route_id travel_route_id INT NOT NULL, CHANGE other_expenses other_expenses INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE wpt_travel_expense_audit CHANGE other_expenses other_expenses INT DEFAULT 0');
        $this->addSql('ALTER TABLE wpt_travel_expense_audit ADD CONSTRAINT rev_27f57b88f7b19e7f0da66637d23c7c37_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wpt_work_day_audit ADD CONSTRAINT rev_5078b049add7547b5901b17a05bf1608_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wpt_work_tutor_answered_survey_audit ADD CONSTRAINT rev_9b088cb8b5a15bf14b41b058e4fb67c5_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE wlt_contact_project_audit');
        $this->addSql('DROP TABLE wlt_contact_student_enrollment_audit');
        $this->addSql('DROP TABLE wlt_learning_program_activity_realization_audit');
        $this->addSql('DROP TABLE wlt_meeting_student_enrollment_audit');
        $this->addSql('DROP TABLE wlt_meeting_teacher_audit');
        $this->addSql('DROP TABLE wlt_project_group_audit');
        $this->addSql('DROP TABLE wlt_project_student_enrollment_audit');
        $this->addSql('DROP TABLE wlt_travel_expense_agreement_audit');
        $this->addSql('DROP TABLE wlt_tracking_audit');
        $this->addSql('DROP TABLE wpt_activity_criterion_audit');
        $this->addSql('DROP TABLE wpt_contact_agreement_audit');
        $this->addSql('DROP TABLE wpt_contact_student_enrollment_audit');
        $this->addSql('DROP TABLE wpt_travel_expense_agreement_audit');
        $this->addSql('ALTER TABLE answered_survey_audit DROP FOREIGN KEY rev_b49bd09be566418816c3b13bd208621c_fk');
        $this->addSql('ALTER TABLE answered_survey_question_audit DROP FOREIGN KEY rev_2e8f92797ef775558ce93daa1c47f1fd_fk');
        $this->addSql('ALTER TABLE company_audit DROP FOREIGN KEY rev_3af728e1cf16bdb0f83bb90e3b1af48a_fk');
        $this->addSql('ALTER TABLE edu_academic_year_audit DROP FOREIGN KEY rev_32b80cc3e6e41d58d2de465d160f9958_fk');
        $this->addSql('ALTER TABLE edu_contact_method_audit DROP FOREIGN KEY rev_f0caa067d32baecb08ec4f3fc820d0a6_fk');
        $this->addSql('ALTER TABLE person_audit DROP FOREIGN KEY rev_907be00c9c366335b3359c1e8e2f6227_fk');
        $this->addSql('ALTER TABLE role CHANGE person_id person_id INT DEFAULT NULL, CHANGE organization_id organization_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE role_audit DROP FOREIGN KEY rev_92317bf7adb4788531df0b1cb910b5fc_fk');
        $this->addSql('ALTER TABLE survey_audit DROP FOREIGN KEY rev_0b043444544b35c998515597d9c72406_fk');
        $this->addSql('ALTER TABLE survey_question_audit DROP FOREIGN KEY rev_c3e747f7590f2b4f4a7532c099a72b37_fk');
        $this->addSql('ALTER TABLE wlt_activity_realization_grade_audit DROP FOREIGN KEY rev_f465a2778ab91cc6186229f95700df6f_fk');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization_audit DROP FOREIGN KEY rev_3aa810fb5b23d95bfecc473791749abf_fk');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization_comment CHANGE person_id person_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization_comment_audit DROP FOREIGN KEY rev_935c6eb03972c07b2fc1ea23e44b8f95_fk');
        $this->addSql('ALTER TABLE wlt_contact_audit DROP FOREIGN KEY rev_1a3b3959a5ced6400bc91d305339a7c0_fk');
        $this->addSql('ALTER TABLE wlt_educational_tutor_answered_survey_audit DROP FOREIGN KEY rev_68c9c40ff36ffd2b1378aa4b2746dcac_fk');
        $this->addSql('ALTER TABLE wlt_learning_program_audit DROP FOREIGN KEY rev_3e4a8a47037e8657861e40d29d7511a6_fk');
        $this->addSql('ALTER TABLE wlt_meeting_audit DROP FOREIGN KEY rev_4f5b29d8966971ed79a052aacd98c68f_fk');
        $this->addSql('ALTER TABLE wlt_project_audit DROP FOREIGN KEY rev_e55594229ab1a86fcf85548ae1e37a5e_fk');
        $this->addSql('ALTER TABLE wlt_student_answered_survey_audit DROP FOREIGN KEY rev_7233771bba2039be6047b538a296dbb3_fk');
        $this->addSql('ALTER TABLE wlt_travel_expense CHANGE travel_route_id travel_route_id INT DEFAULT NULL, CHANGE other_expenses other_expenses INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wlt_travel_expense_audit DROP FOREIGN KEY rev_a88868b0c6712e0abd1e296b1217a283_fk');
        $this->addSql('ALTER TABLE wlt_travel_expense_audit CHANGE other_expenses other_expenses INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wlt_work_day_audit DROP FOREIGN KEY rev_a182ece7d34180bb9b41759ce4dafebc_fk');
        $this->addSql('ALTER TABLE wlt_work_tutor_answered_survey_audit DROP FOREIGN KEY rev_583e3ede99c93193ba43436008b164e7_fk');
        $this->addSql('ALTER TABLE workcenter_audit DROP FOREIGN KEY rev_54a4a65186f56b9de79ff6f4b726b582_fk');
        $this->addSql('ALTER TABLE wpt_activity_audit DROP FOREIGN KEY rev_9c2622768c8baa59674e5941fb6223f9_fk');
        $this->addSql('ALTER TABLE wpt_agreement_audit DROP FOREIGN KEY rev_c44410e99c46f4c316201779be629ca6_fk');
        $this->addSql('ALTER TABLE wpt_contact_audit DROP FOREIGN KEY rev_14a73b1dc586567879897aac041a9700_fk');
        $this->addSql('ALTER TABLE wpt_educational_tutor_answered_survey_audit DROP FOREIGN KEY rev_d3a1a7f9086d9bd8a93c67e81b8519eb_fk');
        $this->addSql('ALTER TABLE wpt_shift_audit DROP FOREIGN KEY rev_71f73a2d4dc70e8d55eaad661689e458_fk');
        $this->addSql('ALTER TABLE wpt_student_answered_survey_audit DROP FOREIGN KEY rev_18ec14100e728ec30e97808504c4bcae_fk');
        $this->addSql('ALTER TABLE wpt_travel_expense CHANGE travel_route_id travel_route_id INT DEFAULT NULL, CHANGE other_expenses other_expenses INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wpt_travel_expense_audit DROP FOREIGN KEY rev_27f57b88f7b19e7f0da66637d23c7c37_fk');
        $this->addSql('ALTER TABLE wpt_travel_expense_audit CHANGE other_expenses other_expenses INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wpt_work_day_audit DROP FOREIGN KEY rev_5078b049add7547b5901b17a05bf1608_fk');
        $this->addSql('ALTER TABLE wpt_work_tutor_answered_survey_audit DROP FOREIGN KEY rev_9b088cb8b5a15bf14b41b058e4fb67c5_fk');
    }
}
