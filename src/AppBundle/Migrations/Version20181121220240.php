<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181121220240 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE edu_student_enrollment (id INT AUTO_INCREMENT NOT NULL, person_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_7824A430217BBB47 (person_id), INDEX IDX_7824A430FE54D947 (group_id), UNIQUE INDEX UNIQ_7824A430217BBB47FE54D947 (person_id, group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE edu_student_enrollment ADD CONSTRAINT FK_7824A430217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE edu_student_enrollment ADD CONSTRAINT FK_7824A430FE54D947 FOREIGN KEY (group_id) REFERENCES edu_group (id)');
        $this->addSql('DROP TABLE student_enrollment');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE student_enrollment (id INT AUTO_INCREMENT NOT NULL, person_id INT NOT NULL, group_id INT NOT NULL, UNIQUE INDEX UNIQ_36033A00217BBB47FE54D947 (person_id, group_id), INDEX IDX_36033A00217BBB47 (person_id), INDEX IDX_36033A00FE54D947 (group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE student_enrollment ADD CONSTRAINT FK_36033A00217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE student_enrollment ADD CONSTRAINT FK_36033A00FE54D947 FOREIGN KEY (group_id) REFERENCES edu_group (id)');
        $this->addSql('DROP TABLE edu_student_enrollment');
    }
}
