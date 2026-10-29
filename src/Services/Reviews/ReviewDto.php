<?php

declare(strict_types=1);

namespace Kasko\Services\Reviews;

use DateTimeImmutable;

/**
 * ReviewDto — normalized review payload returned by providers.
 *
 * Providers (ReviewProviderInterface implementations) parse their
 * API-specific response into these objects. The sync command
 * consumes DTOs exclusively — it never sees provider HTTP shapes —
 * so adding a new provider never changes the command or the entity.
 *
 * `externalUpdatedAt` is required (not null) because the sync
 * depends on it to decide UPDATE vs. unchanged. Providers that
 * omit a per-review update timestamp should pass the retrieval
 * time; the sync will treat every row as potentially updated,
 * which is correct for sources that don't advertise mutability.
 */
final readonly class ReviewDto
{
    public function __construct(
        public string $source,
        public string $externalId,
        public string $author,
        public string $reviewText,
        public int $rating,
        public int $ratingScale,
        public ?string $city,
        public ?string $state,
        public ?string $sourceUrl,
        public DateTimeImmutable $externalUpdatedAt,
    ) {
    }
}
