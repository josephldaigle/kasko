<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Reviews sync columns on customer_review.
 *
 * Adds the metadata needed by the Google reviews sync (source
 * provider key, provider-side id, provider update timestamp,
 * local timestamps) and migrates the single-column
 * `reviewer_location` into nullable `city` + `state`. The two
 * legacy rows seeded by the old hardcoded CMS data use
 * `"City, ST"` location strings, so backfill splits on the
 * literal `", "` separator before dropping the column.
 *
 * review_text widens to LONGTEXT so Google reviews longer than
 * 255 chars survive import; source_url becomes nullable because
 * Google does not expose per-review URLs; source_name narrows
 * to VARCHAR(64) to match the entity.
 *
 * Backfill rules for existing rows:
 *   - source           := 'google' (both legacy rows were Google)
 *   - external_id      := NULL     (unknown — treat as manually seeded)
 *   - created_at       := CURRENT_TIMESTAMP at migration time
 *   - updated_at       := CURRENT_TIMESTAMP at migration time
 *   - city/state       := parsed from reviewer_location via
 *                         SUBSTRING_INDEX(reviewer_location, ', ', ±1)
 *
 * Unique index on (source, external_id) enforces one row per
 * provider review. MySQL counts NULLs as distinct, so multiple
 * rows with external_id NULL within the same source remain valid.
 */
final class Version20261016122909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reviews-sync columns to customer_review; migrate reviewer_location → city/state; widen review_text to LONGTEXT; relax source_url to nullable; unique (source, external_id).';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on 'mysql'."
        );

        $this->addSql("ALTER TABLE customer_review ADD source VARCHAR(32) DEFAULT 'google' NOT NULL");
        $this->addSql("ALTER TABLE customer_review ADD external_id VARCHAR(191) DEFAULT NULL");
        $this->addSql("ALTER TABLE customer_review ADD city VARCHAR(128) DEFAULT NULL");
        $this->addSql("ALTER TABLE customer_review ADD state VARCHAR(64) DEFAULT NULL");
        $this->addSql("ALTER TABLE customer_review ADD external_updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql("ALTER TABLE customer_review ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql("ALTER TABLE customer_review ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '(DC2Type:datetime_immutable)'");

        // Backfill city/state from reviewer_location before dropping it.
        // SUBSTRING_INDEX returns the whole string when the separator
        // isn't found, so rows without ", " leave `city` populated with
        // the raw value and `state` null — the derived getter still
        // renders it correctly on the public site.
        $this->addSql("UPDATE customer_review SET city = TRIM(SUBSTRING_INDEX(reviewer_location, ', ', 1)) WHERE reviewer_location <> '' AND reviewer_location IS NOT NULL");
        $this->addSql("UPDATE customer_review SET state = TRIM(SUBSTRING_INDEX(reviewer_location, ', ', -1)) WHERE reviewer_location LIKE '%, %'");

        $this->addSql("ALTER TABLE customer_review DROP COLUMN reviewer_location");

        $this->addSql("ALTER TABLE customer_review MODIFY review_text LONGTEXT NOT NULL");
        $this->addSql("ALTER TABLE customer_review MODIFY source_url VARCHAR(512) DEFAULT NULL");
        $this->addSql("ALTER TABLE customer_review MODIFY source_name VARCHAR(64) NOT NULL");

        $this->addSql('CREATE UNIQUE INDEX uniq_customer_review_source_external ON customer_review (source, external_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on 'mysql'."
        );

        $this->addSql('DROP INDEX uniq_customer_review_source_external ON customer_review');

        $this->addSql("ALTER TABLE customer_review MODIFY source_name VARCHAR(255) NOT NULL");

        $this->addSql("UPDATE customer_review SET source_url = '' WHERE source_url IS NULL");
        $this->addSql("ALTER TABLE customer_review MODIFY source_url VARCHAR(255) NOT NULL");
        $this->addSql("ALTER TABLE customer_review MODIFY review_text VARCHAR(255) NOT NULL");

        $this->addSql("ALTER TABLE customer_review ADD reviewer_location VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addSql("UPDATE customer_review SET reviewer_location = TRIM(BOTH ', ' FROM CONCAT(COALESCE(city, ''), ', ', COALESCE(state, '')))");

        $this->addSql('ALTER TABLE customer_review DROP source');
        $this->addSql('ALTER TABLE customer_review DROP external_id');
        $this->addSql('ALTER TABLE customer_review DROP city');
        $this->addSql('ALTER TABLE customer_review DROP state');
        $this->addSql('ALTER TABLE customer_review DROP external_updated_at');
        $this->addSql('ALTER TABLE customer_review DROP created_at');
        $this->addSql('ALTER TABLE customer_review DROP updated_at');
    }
}
