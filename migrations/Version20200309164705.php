<?php

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200309164705 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE wpt_travel_expense (id INT AUTO_INCREMENT NOT NULL, teacher_id INT NOT NULL, travel_route_id INT DEFAULT NULL, from_date_time DATETIME NOT NULL, to_date_time DATETIME NOT NULL, other_expenses_description LONGTEXT DEFAULT NULL, other_expenses INT DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, INDEX IDX_C76D045341807E1D (teacher_id), INDEX IDX_C76D04535E9DD7A3 (travel_route_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_travel_expense_agreement (travel_expense_id INT NOT NULL, agreement_id INT NOT NULL, INDEX IDX_2E628311AA203AA8 (travel_expense_id), INDEX IDX_2E62831124890B2B (agreement_id), PRIMARY KEY(travel_expense_id, agreement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_travel_route (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, description VARCHAR(255) NOT NULL, verified TINYINT(1) NOT NULL, distance INT NOT NULL, INDEX IDX_F66A658832C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wpt_travel_expense ADD CONSTRAINT FK_C76D045341807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE wpt_travel_expense ADD CONSTRAINT FK_C76D04535E9DD7A3 FOREIGN KEY (travel_route_id) REFERENCES edu_travel_route (id)');
        $this->addSql('ALTER TABLE wpt_travel_expense_agreement ADD CONSTRAINT FK_2E628311AA203AA8 FOREIGN KEY (travel_expense_id) REFERENCES wpt_travel_expense (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wpt_travel_expense_agreement ADD CONSTRAINT FK_2E62831124890B2B FOREIGN KEY (agreement_id) REFERENCES wpt_agreement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE edu_travel_route ADD CONSTRAINT FK_F66A658832C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wpt_travel_expense_agreement DROP FOREIGN KEY FK_2E628311AA203AA8');
        $this->addSql('ALTER TABLE wpt_travel_expense DROP FOREIGN KEY FK_C76D04535E9DD7A3');
        $this->addSql('DROP TABLE wpt_travel_expense');
        $this->addSql('DROP TABLE wpt_travel_expense_agreement');
        $this->addSql('DROP TABLE edu_travel_route');
    }
}
