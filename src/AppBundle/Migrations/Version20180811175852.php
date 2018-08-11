<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180811175852 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE organization (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(255) NOT NULL, zip_code VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, fax_number VARCHAR(255) DEFAULT NULL, email_address VARCHAR(255) DEFAULT NULL, web_site VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_C1EE637C77153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE manager (organization_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_FA2425B932C8A3DE (organization_id), INDEX IDX_FA2425B9A76ED395 (user_id), PRIMARY KEY(organization_id, user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE person (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, internal_code VARCHAR(255) DEFAULT NULL, gender INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE membership (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, organization_id INT NOT NULL, valid_from DATETIME NOT NULL, valid_until DATETIME DEFAULT NULL, INDEX IDX_86FFD285A76ED395 (user_id), INDEX IDX_86FFD28532C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT NOT NULL, default_organization_id INT DEFAULT NULL, login_username VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, enabled TINYINT(1) NOT NULL, global_administrator TINYINT(1) NOT NULL, email_address VARCHAR(255) DEFAULT NULL, token VARCHAR(255) DEFAULT NULL, token_type VARCHAR(255) DEFAULT NULL, token_expiration DATETIME DEFAULT NULL, last_access DATETIME DEFAULT NULL, blocked_until DATETIME DEFAULT NULL, external_check TINYINT(1) NOT NULL, allow_external_check TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649D6FA26E8 (login_username), UNIQUE INDEX UNIQ_8D93D64935C246D5 (password), UNIQUE INDEX UNIQ_8D93D649B08E074E (email_address), INDEX IDX_8D93D649AA9E0B02 (default_organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE manager ADD CONSTRAINT FK_FA2425B932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE manager ADD CONSTRAINT FK_FA2425B9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD285A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD28532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649BF396750 FOREIGN KEY (id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649AA9E0B02 FOREIGN KEY (default_organization_id) REFERENCES organization (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE manager DROP FOREIGN KEY FK_FA2425B932C8A3DE');
        $this->addSql('ALTER TABLE membership DROP FOREIGN KEY FK_86FFD28532C8A3DE');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649AA9E0B02');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649BF396750');
        $this->addSql('ALTER TABLE manager DROP FOREIGN KEY FK_FA2425B9A76ED395');
        $this->addSql('ALTER TABLE membership DROP FOREIGN KEY FK_86FFD285A76ED395');
        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP TABLE manager');
        $this->addSql('DROP TABLE person');
        $this->addSql('DROP TABLE membership');
        $this->addSql('DROP TABLE user');
    }
}
