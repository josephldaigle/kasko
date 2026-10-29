<?php

declare(strict_types=1);

namespace Kasko\Entity;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Kasko\Repository\CustomerReviewRepository;

/**
 * CustomerReview — a single review displayed on the public site.
 *
 * The entity backs two Twig consumers that pre-date the sync system:
 *   - templates/components/testimonials.html.twig (carousel, every review)
 *   - templates/layouts/service-landing.html.twig (first review only)
 *
 * Both templates read `reviewScore`, `reviewScale`, `reviewText`,
 * `reviewerName`, `reviewerLocation`, `sourceUrl`, `sourceName`.
 * To keep those templates unchanged under the new DB-backed
 * reviews, the entity preserves every one of those getter names.
 * `reviewerLocation` is now derived from the stored `city`/`state`
 * pair so Google-sourced reviews don't need to invent a combined
 * location string.
 *
 * Sync-metadata columns (source, externalId, externalUpdatedAt,
 * createdAt, updatedAt) are populated by the sync command
 * (src/Command/SyncReviewsCommand.php). `source` keys the
 * provider that owns the row — a sync run only touches rows whose
 * source matches the provider being synced, so manual rows (or
 * rows owned by other providers) are never modified or deleted by
 * a single-provider sync. All timestamps are stored UTC.
 */
#[ORM\Entity(repositoryClass: CustomerReviewRepository::class)]
#[ORM\Table(name: 'customer_review')]
#[ORM\UniqueConstraint(name: 'uniq_customer_review_source_external', columns: ['source', 'external_id'])]
#[ORM\HasLifecycleCallbacks]
class CustomerReview
{
    public const SOURCE_GOOGLE = 'google';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * Provider key — matches ReviewProviderInterface::getSourceKey().
     * `google` is seeded by migration for the two legacy rows.
     */
    #[ORM\Column(type: 'string', length: 32, options: ['default' => self::SOURCE_GOOGLE])]
    private string $source = self::SOURCE_GOOGLE;

    /**
     * Provider-side identifier. For Google Business Profile reviews
     * this is the `reviewId` portion of the API resource name
     * `accounts/{a}/locations/{l}/reviews/{reviewId}`. Nullable so
     * manually-entered rows (no sync origin) remain valid.
     */
    #[ORM\Column(name: 'external_id', type: 'string', length: 191, nullable: true)]
    private ?string $externalId = null;

    #[ORM\Column(name: 'review_score', type: 'integer')]
    private int $reviewScore = 0;

    #[ORM\Column(name: 'review_scale', type: 'integer')]
    private int $reviewScale = 5;

    #[ORM\Column(name: 'reviewer_name', type: 'string', length: 255)]
    private string $reviewerName = '';

    /**
     * City and state are stored separately — Google provides neither
     * on review payloads, so these stay null for Google-sourced rows
     * and the derived `getReviewerLocation()` returns an empty
     * string. Pre-existing hardcoded rows are backfilled by the
     * migration that introduces the columns.
     */
    #[ORM\Column(name: 'city', type: 'string', length: 128, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(name: 'state', type: 'string', length: 64, nullable: true)]
    private ?string $state = null;

    /**
     * Review body. TEXT so Google reviews (>255 chars) survive
     * import. Empty-string stored verbatim when Google returns no
     * comment — only a star rating.
     */
    #[ORM\Column(name: 'review_text', type: 'text')]
    private string $reviewText = '';

    #[ORM\Column(name: 'source_name', type: 'string', length: 64)]
    private string $sourceName = 'Google';

    /**
     * Public URL the review footer links to. Google does not expose a
     * per-review URL — the sync writes the business's canonical
     * reviews page (configured via GOOGLE_BUSINESS_REVIEWS_URL) to
     * every row. Nullable so missing config degrades gracefully.
     */
    #[ORM\Column(name: 'source_url', type: 'string', length: 512, nullable: true)]
    private ?string $sourceUrl = null;

    /**
     * Provider-reported last-modification timestamp (UTC). Drives the
     * sync UPDATE decision: if Google's updateTime is newer than
     * this value, the local row is replaced.
     */
    #[ORM\Column(name: 'external_updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $externalUpdatedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getReviewScore(): int
    {
        return $this->reviewScore;
    }

    public function setReviewScore(int $reviewScore): self
    {
        $this->reviewScore = $reviewScore;
        return $this;
    }

    public function getReviewScale(): int
    {
        return $this->reviewScale;
    }

    public function setReviewScale(int $reviewScale): self
    {
        $this->reviewScale = $reviewScale;
        return $this;
    }

    public function getReviewerName(): string
    {
        return $this->reviewerName;
    }

    public function setReviewerName(string $reviewerName): self
    {
        $this->reviewerName = $reviewerName;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): self
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Derived accessor consumed by the existing Twig templates. Both
     * templates render it verbatim after the reviewer name
     * (`{{ review.reviewerName }} · {{ review.reviewerLocation }}`),
     * so composing `City, ST` here is the whole backward-compat
     * story. Returns `''` when neither field is populated — the
     * templates already special-case truthiness on it.
     */
    public function getReviewerLocation(): string
    {
        if ($this->city !== null && $this->state !== null) {
            return $this->city . ', ' . $this->state;
        }
        return $this->city ?? $this->state ?? '';
    }

    public function getReviewText(): string
    {
        return $this->reviewText;
    }

    public function setReviewText(string $reviewText): self
    {
        $this->reviewText = $reviewText;
        return $this;
    }

    public function getSourceName(): string
    {
        return $this->sourceName;
    }

    public function setSourceName(string $sourceName): self
    {
        $this->sourceName = $sourceName;
        return $this;
    }

    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function setSourceUrl(?string $sourceUrl): self
    {
        $this->sourceUrl = $sourceUrl;
        return $this;
    }

    public function getExternalUpdatedAt(): ?DateTimeImmutable
    {
        return $this->externalUpdatedAt;
    }

    public function setExternalUpdatedAt(?DateTimeImmutable $externalUpdatedAt): self
    {
        $this->externalUpdatedAt = $externalUpdatedAt;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
