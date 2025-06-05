<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250605103756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE itp_travel_expense (id INT AUTO_INCREMENT NOT NULL, teacher_id INT NOT NULL, travel_route_id INT NOT NULL, from_date_time DATETIME NOT NULL, to_date_time DATETIME NOT NULL, other_expenses_description LONGTEXT DEFAULT NULL, other_expenses INT DEFAULT 0 NOT NULL, description VARCHAR(255) DEFAULT NULL, INDEX IDX_57DA218A41807E1D (teacher_id), INDEX IDX_57DA218A5E9DD7A3 (travel_route_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_travel_expense_training_program (travel_expense_id INT NOT NULL, training_program_id INT NOT NULL, INDEX IDX_323264A1AA203AA8 (travel_expense_id), INDEX IDX_323264A18406BD6C (training_program_id), PRIMARY KEY(travel_expense_id, training_program_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_travel_expense_audit (id INT NOT NULL, rev INT NOT NULL, teacher_id INT DEFAULT NULL, travel_route_id INT DEFAULT NULL, from_date_time DATETIME DEFAULT NULL, to_date_time DATETIME DEFAULT NULL, other_expenses_description LONGTEXT DEFAULT NULL, other_expenses INT DEFAULT 0, description VARCHAR(255) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_bae1d08b03654151182f7f59945a6497_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE itp_travel_expense_training_program_audit (travel_expense_id INT NOT NULL, training_program_id INT NOT NULL, rev INT NOT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_69545932f5ea1b2ed3d2febd50185e25_idx (rev), PRIMARY KEY(travel_expense_id, training_program_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE itp_travel_expense ADD CONSTRAINT FK_57DA218A41807E1D FOREIGN KEY (teacher_id) REFERENCES edu_teacher (id)');
        $this->addSql('ALTER TABLE itp_travel_expense ADD CONSTRAINT FK_57DA218A5E9DD7A3 FOREIGN KEY (travel_route_id) REFERENCES edu_travel_route (id)');
        $this->addSql('ALTER TABLE itp_travel_expense_training_program ADD CONSTRAINT FK_323264A1AA203AA8 FOREIGN KEY (travel_expense_id) REFERENCES itp_travel_expense (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_travel_expense_training_program ADD CONSTRAINT FK_323264A18406BD6C FOREIGN KEY (training_program_id) REFERENCES itp_training_program (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE itp_travel_expense_audit ADD CONSTRAINT rev_bae1d08b03654151182f7f59945a6497_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE edu_performance_scale_value CHANGE negative_grade negative_grade TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE itp_travel_expense DROP FOREIGN KEY FK_57DA218A41807E1D');
        $this->addSql('ALTER TABLE itp_travel_expense DROP FOREIGN KEY FK_57DA218A5E9DD7A3');
        $this->addSql('ALTER TABLE itp_travel_expense_training_program DROP FOREIGN KEY FK_323264A1AA203AA8');
        $this->addSql('ALTER TABLE itp_travel_expense_training_program DROP FOREIGN KEY FK_323264A18406BD6C');
        $this->addSql('ALTER TABLE itp_travel_expense_audit DROP FOREIGN KEY rev_bae1d08b03654151182f7f59945a6497_fk');
        $this->addSql('DROP TABLE itp_travel_expense');
        $this->addSql('DROP TABLE itp_travel_expense_training_program');
        $this->addSql('DROP TABLE itp_travel_expense_audit');
        $this->addSql('DROP TABLE itp_travel_expense_training_program_audit');
        $this->addSql('ALTER TABLE edu_performance_scale_value CHANGE negative_grade negative_grade TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
