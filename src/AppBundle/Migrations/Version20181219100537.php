<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181219100537 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE wlt_learning_program (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, training_id INT NOT NULL, INDEX IDX_5EB2E44D979B1AD6 (company_id), INDEX IDX_5EB2E44DBEFD98D1 (training_id), UNIQUE INDEX UNIQ_5EB2E44D979B1AD6BEFD98D1 (company_id, training_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_learning_program_activity_realization (learning_program_id INT NOT NULL, activity_realization_id INT NOT NULL, INDEX IDX_7EADE17BED94D8BC (learning_program_id), INDEX IDX_7EADE17B862E876A (activity_realization_id), PRIMARY KEY(learning_program_id, activity_realization_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wlt_learning_program ADD CONSTRAINT FK_5EB2E44D979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE wlt_learning_program ADD CONSTRAINT FK_5EB2E44DBEFD98D1 FOREIGN KEY (training_id) REFERENCES edu_training (id)');
        $this->addSql('ALTER TABLE wlt_learning_program_activity_realization ADD CONSTRAINT FK_7EADE17BED94D8BC FOREIGN KEY (learning_program_id) REFERENCES wlt_learning_program (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_learning_program_activity_realization ADD CONSTRAINT FK_7EADE17B862E876A FOREIGN KEY (activity_realization_id) REFERENCES wlt_activity_realization (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wlt_learning_program_activity_realization DROP FOREIGN KEY FK_7EADE17BED94D8BC');
        $this->addSql('DROP TABLE wlt_learning_program');
        $this->addSql('DROP TABLE wlt_learning_program_activity_realization');
    }
}
