<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181120121514 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE edu_group_tutor (group_id INT NOT NULL, teacher_id INT NOT NULL, INDEX IDX_78832E94FE54D947 (group_id), INDEX IDX_78832E9441807E1D (teacher_id), PRIMARY KEY(group_id, teacher_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE edu_group_tutor ADD CONSTRAINT FK_78832E94FE54D947 FOREIGN KEY (group_id) REFERENCES edu_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE edu_group_tutor ADD CONSTRAINT FK_78832E9441807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE edu_group DROP FOREIGN KEY FK_4C368872208F64F1');
        $this->addSql('DROP INDEX IDX_4C368872208F64F1 ON edu_group');
        $this->addSql('ALTER TABLE edu_group DROP tutor_id');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE edu_group_tutor');
        $this->addSql('ALTER TABLE edu_group ADD tutor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE edu_group ADD CONSTRAINT FK_4C368872208F64F1 FOREIGN KEY (tutor_id) REFERENCES edu_teacher (id)');
        $this->addSql('CREATE INDEX IDX_4C368872208F64F1 ON edu_group (tutor_id)');
    }
}
