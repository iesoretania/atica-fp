<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181114220735 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE edu_competency (id INT AUTO_INCREMENT NOT NULL, training_id INT NOT NULL, code VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, INDEX IDX_237FAB0BBEFD98D1 (training_id), UNIQUE INDEX UNIQ_237FAB0BBEFD98D177153098 (training_id, code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_learning_outcome (id INT AUTO_INCREMENT NOT NULL, subject_id INT NOT NULL, code VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, INDEX IDX_301888C623EDC87 (subject_id), UNIQUE INDEX UNIQ_301888C623EDC8777153098 (subject_id, code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE edu_competency ADD CONSTRAINT FK_237FAB0BBEFD98D1 FOREIGN KEY (training_id) REFERENCES edu_training (id)');
        $this->addSql('ALTER TABLE edu_learning_outcome ADD CONSTRAINT FK_301888C623EDC87 FOREIGN KEY (subject_id) REFERENCES edu_subject (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE edu_competency');
        $this->addSql('DROP TABLE edu_learning_outcome');
    }
}
