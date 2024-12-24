<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241007062837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP FOREIGN KEY FK_BD86F078FE19A1A8');
        $this->addSql('CREATE TABLE edu_performance_scale (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, description VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, INDEX IDX_97FBF6F932C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_performance_scale_audit (id INT NOT NULL, rev INT NOT NULL, organization_id INT DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_872a21aa478051cdedd389213a503b28_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_performance_scale_value (id INT AUTO_INCREMENT NOT NULL, performance_scale_id INT NOT NULL, description VARCHAR(255) NOT NULL, numeric_grade INT NOT NULL, notes LONGTEXT DEFAULT NULL, INDEX IDX_CB9D66577C2C26C0 (performance_scale_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edu_performance_scale_value_audit (id INT NOT NULL, rev INT NOT NULL, performance_scale_id INT DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, numeric_grade INT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_251f0c8c6a4a618acff9d2ea364b6c7d_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO edu_performance_scale (id, organization_id, description, enabled) SELECT id, organization_id, name, 1 FROM wlt_project');
        $this->addSql('INSERT INTO edu_performance_scale_value (id, performance_scale_id, description, numeric_grade, notes) SELECT id, project_id, description, numeric_grade, notes FROM wlt_activity_realization_grade');
        $this->addSql('ALTER TABLE edu_performance_scale ADD CONSTRAINT FK_97FBF6F932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE edu_performance_scale_audit ADD CONSTRAINT rev_872a21aa478051cdedd389213a503b28_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE edu_performance_scale_value ADD CONSTRAINT FK_CB9D66577C2C26C0 FOREIGN KEY (performance_scale_id) REFERENCES edu_performance_scale (id)');
        $this->addSql('ALTER TABLE edu_performance_scale_value_audit ADD CONSTRAINT rev_251f0c8c6a4a618acff9d2ea364b6c7d_fk FOREIGN KEY (rev) REFERENCES revisions (id)');
        $this->addSql('ALTER TABLE wlt_activity_realization_grade DROP FOREIGN KEY FK_CD1FF777166D1F9C');
        $this->addSql('ALTER TABLE wlt_activity_realization_grade_audit DROP FOREIGN KEY rev_f465a2778ab91cc6186229f95700df6f_fk');
        $this->addSql('DROP TABLE wlt_activity_realization_grade');
        $this->addSql('DROP TABLE wlt_activity_realization_grade_audit');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD CONSTRAINT FK_BD86F078FE19A1A8 FOREIGN KEY (grade_id) REFERENCES edu_performance_scale_value (id)');
        $this->addSql('ALTER TABLE wlt_project ADD performance_scale_id INT DEFAULT NULL');
        $this->addSql('UPDATE wlt_project SET performance_scale_id = id');
        $this->addSql('ALTER TABLE wlt_project ADD CONSTRAINT FK_E4D36E417C2C26C0 FOREIGN KEY (performance_scale_id) REFERENCES edu_performance_scale (id)');
        $this->addSql('CREATE INDEX IDX_E4D36E417C2C26C0 ON wlt_project (performance_scale_id)');
        $this->addSql('ALTER TABLE wlt_project_audit ADD performance_scale_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wlt_project DROP FOREIGN KEY FK_E4D36E417C2C26C0');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization DROP FOREIGN KEY FK_BD86F078FE19A1A8');
        $this->addSql('CREATE TABLE wlt_activity_realization_grade (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_spanish_ci`, numeric_grade INT NOT NULL, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_spanish_ci`, INDEX IDX_CD1FF777166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_spanish_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE wlt_activity_realization_grade_audit (id INT NOT NULL, rev INT NOT NULL, project_id INT DEFAULT NULL, description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_spanish_ci`, numeric_grade INT DEFAULT NULL, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_spanish_ci`, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_spanish_ci`, INDEX rev_f465a2778ab91cc6186229f95700df6f_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_spanish_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('INSERT INTO wlt_activity_realization_grade (id, project_id, description, numeric_grade, notes) SELECT id, performance_scale_id, description, numeric_grade, notes FROM edu_performance_scale_value');
        $this->addSql('ALTER TABLE wlt_activity_realization_grade ADD CONSTRAINT FK_CD1FF777166D1F9C FOREIGN KEY (project_id) REFERENCES wlt_project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE wlt_activity_realization_grade_audit ADD CONSTRAINT rev_f465a2778ab91cc6186229f95700df6f_fk FOREIGN KEY (rev) REFERENCES revisions (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE edu_performance_scale DROP FOREIGN KEY FK_97FBF6F932C8A3DE');
        $this->addSql('ALTER TABLE edu_performance_scale_audit DROP FOREIGN KEY rev_872a21aa478051cdedd389213a503b28_fk');
        $this->addSql('ALTER TABLE edu_performance_scale_value DROP FOREIGN KEY FK_CB9D66577C2C26C0');
        $this->addSql('ALTER TABLE edu_performance_scale_value_audit DROP FOREIGN KEY rev_251f0c8c6a4a618acff9d2ea364b6c7d_fk');
        $this->addSql('DROP TABLE edu_performance_scale');
        $this->addSql('DROP TABLE edu_performance_scale_audit');
        $this->addSql('DROP TABLE edu_performance_scale_value');
        $this->addSql('DROP TABLE edu_performance_scale_value_audit');
        $this->addSql('ALTER TABLE wlt_agreement_activity_realization ADD CONSTRAINT FK_BD86F078FE19A1A8 FOREIGN KEY (grade_id) REFERENCES wlt_activity_realization_grade (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP INDEX IDX_E4D36E417C2C26C0 ON wlt_project');
        $this->addSql('ALTER TABLE wlt_project DROP performance_scale_id');
        $this->addSql('ALTER TABLE wlt_project_audit DROP performance_scale_id');
    }
}
