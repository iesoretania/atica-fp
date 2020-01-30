<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200130213703 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $academicYears = $this->connection->createQueryBuilder()
            ->select('ay.id')
            ->addSelect('ay.organization_id')
            ->from('edu_academic_year', 'ay')
            ->execute()
            ->fetchAll();

        foreach ($academicYears as $academicYear) {
            $project = $this->connection->createQueryBuilder()
                ->select('p.id')
                ->from('wlt_project', 'p')
                ->where('p.organization_id = ' . $academicYear['organization_id'])
                ->execute()
                ->fetchAll();

            if (count($project) > 0) {
                $this->connection->createQueryBuilder()
                    ->update('wlt_activity_realization_grade', 'arg')
                    ->set('arg.project_id', $project[0]['id'])
                    ->where('arg.academic_year_id = ' . $academicYear['id'])
                    ->execute();
            }
        }

        $this->addSql('ALTER TABLE wlt_activity_realization_grade DROP FOREIGN KEY FK_CD1FF777C54F3401');
        $this->addSql('DROP INDEX IDX_CD1FF777C54F3401 ON wlt_activity_realization_grade');
        $this->addSql('ALTER TABLE wlt_activity_realization_grade MODIFY project_id INT NOT NULL');
        $this->addSql('ALTER TABLE wlt_activity_realization_grade DROP academic_year_id');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->throwIrreversibleMigrationException("Sorry! Cannot downgrade to 2.0.x");
    }
}
