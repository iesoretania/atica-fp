<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220310091153 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_log DROP FOREIGN KEY FK_9EF0AD16A76ED395');
        $this->addSql('ALTER TABLE membership DROP FOREIGN KEY FK_86FFD285A76ED395');
        $this->addSql('ALTER TABLE person DROP FOREIGN KEY FK_34DCD176A76ED395');
        $this->addSql('ALTER TABLE person ADD login_username VARCHAR(255) DEFAULT NULL, ADD password VARCHAR(255) DEFAULT NULL, ADD force_password_change TINYINT(1) NOT NULL, ADD enabled TINYINT(1) NOT NULL, ADD global_administrator TINYINT(1) NOT NULL, ADD email_address VARCHAR(255) DEFAULT NULL, ADD token VARCHAR(255) DEFAULT NULL, ADD token_type VARCHAR(255) DEFAULT NULL, ADD token_expiration DATETIME DEFAULT NULL, ADD last_access DATETIME DEFAULT NULL, ADD blocked_until DATETIME DEFAULT NULL, ADD external_check TINYINT(1) NOT NULL, ADD allow_external_check TINYINT(1) NOT NULL, ADD default_organization_id INT DEFAULT NULL');
        $this->addSql('UPDATE person p INNER JOIN user u ON u.id = p.user_id SET p.login_username = u.login_username, p.password = u.password, p.force_password_change = u.force_password_change, p.enabled = u.enabled, p.global_administrator = u.global_administrator, p.email_address = u.email_address, p.token = u.token, p.token_type = u.token_type, p.token_expiration = u.token_expiration, p.last_access = u.last_access, p.blocked_until = u.blocked_until, p.external_check = u.external_check, p.allow_external_check = u.allow_external_check, p.default_organization_id = u.default_organization_id');
        $this->addSql('ALTER TABLE person DROP user_id');
        $this->addSql('DROP TABLE membership');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_audit');
        $this->addSql('ALTER TABLE event_log ADD CONSTRAINT FK_9EF0AD16A76ED395 FOREIGN KEY (user_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE person ADD CONSTRAINT FK_34DCD176AA9E0B02 FOREIGN KEY (default_organization_id) REFERENCES organization (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_34DCD176D6FA26E8 ON person (login_username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_34DCD17635C246D5 ON person (password)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_34DCD176B08E074E ON person (email_address)');
        $this->addSql('CREATE INDEX IDX_34DCD176AA9E0B02 ON person (default_organization_id)');
        $this->addSql('ALTER TABLE person_audit ADD login_username VARCHAR(255) DEFAULT NULL, ADD password VARCHAR(255) DEFAULT NULL, ADD force_password_change TINYINT(1) DEFAULT NULL, ADD enabled TINYINT(1) DEFAULT NULL, ADD global_administrator TINYINT(1) DEFAULT NULL, ADD email_address VARCHAR(255) DEFAULT NULL, ADD token VARCHAR(255) DEFAULT NULL, ADD token_type VARCHAR(255) DEFAULT NULL, ADD token_expiration DATETIME DEFAULT NULL, ADD last_access DATETIME DEFAULT NULL, ADD blocked_until DATETIME DEFAULT NULL, ADD external_check TINYINT(1) DEFAULT NULL, ADD allow_external_check TINYINT(1) DEFAULT NULL, CHANGE user_id default_organization_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->throwIrreversibleMigrationException("Sorry! Cannot downgrade to 4.x.x");
    }
}
