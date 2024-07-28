<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220331234101 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            <<<'EOF'
UPDATE wlt_learning_program_activity_realization AS wlpar
    JOIN wlt_activity_realization war on wlpar.activity_realization_id = war.id
    JOIN wlt_learning_program wlp on wlpar.learning_program_id = wlp.id
SET wlpar.activity_realization_id = (SELECT war2.id
                                     FROM wlt_activity_realization war2
                                              JOIN wlt_activity wa2 on war2.activity_id = wa2.id
                                     WHERE war2.code = war.code
                                       AND wa2.project_id = wlp.project_id
)
EOF
        );
        $this->addSql(
            <<<'EOF'
UPDATE wlt_agreement_activity_realization AS waar
         JOIN wlt_activity_realization war on waar.activity_realization_id = war.id
         JOIN wlt_agreement wa on waar.agreement_id = wa.id
SET waar.activity_realization_id = (SELECT war2.id
                                    FROM wlt_activity_realization war2
                                             JOIN wlt_activity wa2 on war2.activity_id = wa2.id
                                    WHERE war2.code = war.code AND wa2.project_id = wa.project_id
)
EOF
        );
        $this->addSql(
            <<<'EOF'
UPDATE wlt_tracking AS wt
    JOIN wlt_activity_realization war on wt.activity_realization_id = war.id
    JOIN wlt_work_day wwd on wt.work_day_id = wwd.id
    JOIN wlt_agreement wa on wwd.agreement_id = wa.id
SET wt.activity_realization_id = (SELECT war2.id
                                    FROM wlt_activity_realization war2
                                             JOIN wlt_activity wa2 on war2.activity_id = wa2.id
                                    WHERE war2.code = war.code AND wa2.project_id = wa.project_id
)
EOF
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
