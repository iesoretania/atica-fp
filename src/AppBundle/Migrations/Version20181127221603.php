<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181127221603 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, person_id INT DEFAULT NULL, organization_id INT DEFAULT NULL, role VARCHAR(20) NOT NULL, INDEX IDX_57698A6A217BBB47 (person_id), INDEX IDX_57698A6A32C8A3DE (organization_id), UNIQUE INDEX UNIQ_57698A6A217BBB4732C8A3DE57698A6A (person_id, organization_id, role), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE role ADD CONSTRAINT FK_57698A6A217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE role ADD CONSTRAINT FK_57698A6A32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('DROP TABLE edu_organization');

        $this->addSql('ALTER TABLE organization ADD current_academic_year_id INT DEFAULT NULL');
        $this->addSql('UPDATE organization SET current_academic_year_id = (SELECT id FROM edu_academic_year WHERE edu_academic_year.organization_id = organization.id ORDER BY edu_academic_year.description DESC LIMIT 1)');

        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C2B06A9F7 FOREIGN KEY (current_academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C1EE637C2B06A9F7 ON organization (current_academic_year_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE edu_organization (organization_id INT NOT NULL, current_academic_year_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_9DECA13B2B06A9F7 (current_academic_year_id), PRIMARY KEY(organization_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE edu_organization ADD CONSTRAINT FK_9DECA13B2B06A9F7 FOREIGN KEY (current_academic_year_id) REFERENCES edu_academic_year (id)');
        $this->addSql('ALTER TABLE edu_organization ADD CONSTRAINT FK_9DECA13B32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('DROP TABLE role');
        $this->addSql('ALTER TABLE organization DROP FOREIGN KEY FK_C1EE637C2B06A9F7');
        $this->addSql('DROP INDEX UNIQ_C1EE637C2B06A9F7 ON organization');
        $this->addSql('ALTER TABLE organization DROP current_academic_year_id');
    }
}
