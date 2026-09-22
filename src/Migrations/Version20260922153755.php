<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add nullable project_description column to form_lead for the
 * Project Description field on the customer quote form. Doctrine
 * maps the entity `type: text` attribute to MySQL LONGTEXT.
 */
final class Version20260922153755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nullable project_description TEXT column to form_lead';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE form_lead ADD project_description LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE form_lead DROP project_description');
    }
}
