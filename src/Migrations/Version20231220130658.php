<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231220130658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE wlt_travel_expense (id INT AUTO_INCREMENT NOT NULL, teacher_id INT NOT NULL, travel_route_id INT DEFAULT NULL, from_date_time DATETIME NOT NULL, to_date_time DATETIME NOT NULL, other_expenses_description LONGTEXT DEFAULT NULL, other_expenses INT DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, INDEX IDX_4524F57F41807E1D (teacher_id), INDEX IDX_4524F57F5E9DD7A3 (travel_route_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_travel_expense_agreement (travel_expense_id INT NOT NULL, agreement_id INT NOT NULL, INDEX IDX_D3D5958BAA203AA8 (travel_expense_id), INDEX IDX_D3D5958B24890B2B (agreement_id), PRIMARY KEY(travel_expense_id, agreement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wlt_travel_expense_audit (id INT NOT NULL, rev INT NOT NULL, teacher_id INT DEFAULT NULL, travel_route_id INT DEFAULT NULL, from_date_time DATETIME DEFAULT NULL, to_date_time DATETIME DEFAULT NULL, other_expenses_description LONGTEXT DEFAULT NULL, other_expenses INT DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_a88868b0c6712e0abd1e296b1217a283_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wpt_travel_expense_audit (id INT NOT NULL, rev INT NOT NULL, teacher_id INT DEFAULT NULL, travel_route_id INT DEFAULT NULL, from_date_time DATETIME DEFAULT NULL, to_date_time DATETIME DEFAULT NULL, other_expenses_description LONGTEXT DEFAULT NULL, other_expenses INT DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_27f57b88f7b19e7f0da66637d23c7c37_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wlt_travel_expense ADD CONSTRAINT FK_4524F57F41807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE wlt_travel_expense ADD CONSTRAINT FK_4524F57F5E9DD7A3 FOREIGN KEY (travel_route_id) REFERENCES edu_travel_route (id)');
        $this->addSql('ALTER TABLE wlt_travel_expense_agreement ADD CONSTRAINT FK_D3D5958BAA203AA8 FOREIGN KEY (travel_expense_id) REFERENCES wlt_travel_expense (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_travel_expense_agreement ADD CONSTRAINT FK_D3D5958B24890B2B FOREIGN KEY (agreement_id) REFERENCES wlt_agreement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wlt_project ADD locked TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE wlt_project_audit ADD locked TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE wpt_agreement ADD locked TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE wpt_agreement_audit ADD locked TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE wpt_visit ADD method_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wpt_visit ADD CONSTRAINT FK_BE387B1C19883967 FOREIGN KEY (method_id) REFERENCES edu_contact_method (id)');
        $this->addSql('CREATE INDEX IDX_BE387B1C19883967 ON wpt_visit (method_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wlt_travel_expense_agreement DROP FOREIGN KEY FK_D3D5958BAA203AA8');
        $this->addSql('DROP TABLE wlt_travel_expense');
        $this->addSql('DROP TABLE wlt_travel_expense_agreement');
        $this->addSql('DROP TABLE wlt_travel_expense_audit');
        $this->addSql('DROP TABLE wpt_travel_expense_audit');
        $this->addSql('ALTER TABLE wlt_project DROP locked');
        $this->addSql('ALTER TABLE wlt_project_audit DROP locked');
        $this->addSql('ALTER TABLE wpt_agreement DROP locked');
        $this->addSql('ALTER TABLE wpt_agreement_audit DROP locked');
        $this->addSql('ALTER TABLE wpt_visit DROP FOREIGN KEY FK_BE387B1C19883967');
        $this->addSql('DROP INDEX IDX_BE387B1C19883967 ON wpt_visit');
        $this->addSql('ALTER TABLE wpt_visit DROP method_id');
    }
}
