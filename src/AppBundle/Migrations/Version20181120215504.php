<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181120215504 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edu_training ADD department_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE edu_training ADD CONSTRAINT FK_2692AD50AE80F5DF FOREIGN KEY (department_id) REFERENCES edu_department (id)');
        $this->addSql('CREATE INDEX IDX_2692AD50AE80F5DF ON edu_training (department_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edu_training DROP FOREIGN KEY FK_2692AD50AE80F5DF');
        $this->addSql('DROP INDEX IDX_2692AD50AE80F5DF ON edu_training');
        $this->addSql('ALTER TABLE edu_training DROP department_id');
    }
}
