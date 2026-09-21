<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * DBAL 4 upgrade: reconcile tenant.roles/tenant.devices to native JSON.
 *
 * The original migration created these as LONGTEXT with a DC2Type:json
 * comment because DBAL 2/3 emitted that for the json type on MySQL. DBAL 4
 * consistently uses native MySQL JSON, and doctrine:schema:validate
 * reports the two columns as out-of-sync until the ALTER runs.
 *
 * The ALTER is data-preserving on MySQL 8: existing JSON strings stored
 * in the LONGTEXT columns are converted to the native JSON type in place.
 */
final class Version20260921193347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'DBAL 4 upgrade: tenant.roles/devices LONGTEXT -> native JSON';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE tenant CHANGE roles roles JSON NOT NULL, CHANGE devices devices JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE tenant CHANGE devices devices LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', CHANGE roles roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\'');
    }
}
