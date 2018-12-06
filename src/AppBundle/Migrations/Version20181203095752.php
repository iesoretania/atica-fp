<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181203095752 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE wlt_activity (id INT AUTO_INCREMENT NOT NULL, subject_id INT NOT NULL, code VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, prior_learning LONGTEXT NOT NULL, INDEX IDX_EAD6D79D23EDC87 (subject_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_activity_competency (activity_id INT NOT NULL, competency_id INT NOT NULL, INDEX IDX_39DDED3181C06096 (activity_id), INDEX IDX_39DDED31FB9F58C (competency_id), PRIMARY KEY(activity_id, competency_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_activity_realization (id INT AUTO_INCREMENT NOT NULL, activity_id INT NOT NULL, code VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, INDEX IDX_BA14088981C06096 (activity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_activity_realization_learning_outcome (activity_realization_id INT NOT NULL, learning_outcome_id INT NOT NULL, INDEX IDX_C17FFB1E862E876A (activity_realization_id), INDEX IDX_C17FFB1E35C2B2D5 (learning_outcome_id), PRIMARY KEY(activity_realization_id, learning_outcome_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wlt_activity ADD CONSTRAINT FK_EAD6D79D23EDC87 FOREIGN KEY (subject_id) REFERENCES edu_subject (id)');
        $this->addSql('ALTER TABLE wlt_activity_competency ADD CONSTRAINT FK_39DDED3181C06096 FOREIGN KEY (activity_id) REFERENCES wlt_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_activity_competency ADD CONSTRAINT FK_39DDED31FB9F58C FOREIGN KEY (competency_id) REFERENCES edu_competency (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_activity_realization ADD CONSTRAINT FK_BA14088981C06096 FOREIGN KEY (activity_id) REFERENCES wlt_activity (id)');
        $this->addSql('ALTER TABLE wlt_activity_realization_learning_outcome ADD CONSTRAINT FK_C17FFB1E862E876A FOREIGN KEY (activity_realization_id) REFERENCES wlt_activity_realization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_activity_realization_learning_outcome ADD CONSTRAINT FK_C17FFB1E35C2B2D5 FOREIGN KEY (learning_outcome_id) REFERENCES edu_learning_outcome (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_activity_competency DROP FOREIGN KEY FK_39DDED3181C06096');
        $this->addSql('ALTER TABLE wlt_activity_realization DROP FOREIGN KEY FK_BA14088981C06096');
        $this->addSql('ALTER TABLE wlt_activity_realization_learning_outcome DROP FOREIGN KEY FK_C17FFB1E862E876A');
        $this->addSql('DROP TABLE wlt_activity');
        $this->addSql('DROP TABLE wlt_activity_competency');
        $this->addSql('DROP TABLE wlt_activity_realization');
        $this->addSql('DROP TABLE wlt_activity_realization_learning_outcome');
    }
}
