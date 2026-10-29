<?php

declare(strict_types=1);

namespace Kasko\Services\Reviews\Google;

use DateTimeImmutable;
use DateTimeZone;
use Kasko\Entity\CustomerReview;
use Kasko\Services\Reviews\ReviewDto;
use Kasko\Services\Reviews\ReviewProviderInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * GoogleReviewsProvider — Google Business Profile API v4 adapter.
 *
 * Endpoint:
 *   GET https://mybusiness.googleapis.com/v4/accounts/{accountId}/locations/{locationId}/reviews
 *       ?pageSize=50&pageToken={next}
 *
 * The v4 host is the only Business Profile surface that returns
 * the complete review set with pagination; the newer Business
 * Information + Places APIs do not expose it. Access requires an
 * allowlisted application and user OAuth (refresh token).
 *
 * Response shape (per the public API reference, as of 2026):
 *   {
 *     "reviews": [
 *       {
 *         "reviewId": "...",
 *         "reviewer": {"displayName": "..."},
 *         "starRating": "FIVE",
 *         "comment": "...",            // optional
 *         "createTime": "RFC3339",
 *         "updateTime": "RFC3339",
 *         "name": "accounts/.../reviews/..."
 *       }
 *     ],
 *     "nextPageToken": "...",          // optional
 *     "totalReviewCount": 123
 *   }
 *
 * Google does not emit a user-facing URL for a review. Every
 * normalized ReviewDto therefore carries the business's canonical
 * reviews-page URL (GOOGLE_BUSINESS_REVIEWS_URL). If that env var
 * is unset the DTO's sourceUrl is null and the Twig templates
 * degrade gracefully (link falls back to `#`).
 */
class GoogleReviewsProvider implements ReviewProviderInterface
{
    private const API_HOST = 'https://mybusiness.googleapis.com';
    private const PAGE_SIZE = 50;
    private const MAX_PAGES = 200; // ~10k reviews cap — a hard stop against runaway pagination.

    private const STAR_RATING_MAP = [
        'ONE'   => 1,
        'TWO'   => 2,
        'THREE' => 3,
        'FOUR'  => 4,
        'FIVE'  => 5,
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly GoogleOAuthClient $oauth,
        private readonly LoggerInterface $logger,
        private readonly string $accountId,
        private readonly string $locationId,
        private readonly string $businessReviewsUrl,
    ) {
    }

    public function getSourceKey(): string
    {
        return CustomerReview::SOURCE_GOOGLE;
    }

    /**
     * Lazily yields every review across every page. Yields nothing
     * if the account has no reviews. Throws RuntimeException on
     * any auth/HTTP/parse failure or page cap exhaustion — the
     * caller MUST interpret an exception as "do not DELETE".
     *
     * @return iterable<ReviewDto>
     */
    public function fetchAll(): iterable
    {
        if ($this->accountId === '' || $this->locationId === '') {
            throw new RuntimeException('Google Business Profile account/location IDs are not configured. Set GOOGLE_BUSINESS_ACCOUNT_ID and GOOGLE_BUSINESS_LOCATION_ID.');
        }

        $accessToken = $this->oauth->getAccessToken();
        $endpoint = sprintf(
            '%s/v4/accounts/%s/locations/%s/reviews',
            self::API_HOST,
            rawurlencode($this->accountId),
            rawurlencode($this->locationId),
        );

        $pageToken = null;
        $pageIndex = 0;
        do {
            if ($pageIndex >= self::MAX_PAGES) {
                throw new RuntimeException(sprintf(
                    'Google reviews pagination exceeded safety cap (%d pages). Aborting before any delete.',
                    self::MAX_PAGES
                ));
            }

            $query = ['pageSize' => self::PAGE_SIZE];
            if ($pageToken !== null) {
                $query['pageToken'] = $pageToken;
            }

            try {
                $response = $this->httpClient->request('GET', $endpoint, [
                    'query'   => $query,
                    'headers' => ['Authorization' => 'Bearer ' . $accessToken],
                    'timeout' => 30,
                ]);
                $status = $response->getStatusCode();
                $payload = $response->toArray(throw: false);
            } catch (\Throwable $e) {
                $this->logger->error('Google reviews list request failed', [
                    'page_index' => $pageIndex,
                    'error'      => $e->getMessage(),
                ]);
                throw new RuntimeException('Google reviews list request failed: ' . $e->getMessage(), previous: $e);
            }

            if ($status !== 200) {
                $this->logger->error('Google reviews endpoint returned error', [
                    'status'     => $status,
                    'page_index' => $pageIndex,
                    'error'      => $payload['error'] ?? null,
                ]);
                throw new RuntimeException(sprintf(
                    'Google reviews endpoint returned HTTP %d on page %d: %s',
                    $status,
                    $pageIndex,
                    $payload['error']['message'] ?? 'unknown error'
                ));
            }

            foreach ($payload['reviews'] ?? [] as $raw) {
                $dto = $this->normalize($raw);
                if ($dto !== null) {
                    yield $dto;
                }
            }

            $pageToken = $payload['nextPageToken'] ?? null;
            ++$pageIndex;
        } while ($pageToken !== null && $pageToken !== '');
    }

    /**
     * Normalize one raw review object into a ReviewDto. Returns
     * null (skip) when the row lacks the identifying fields the
     * sync requires. Never throws for a single malformed review —
     * a single bad record must not abort the entire run.
     */
    private function normalize(array $raw): ?ReviewDto
    {
        $reviewId = $raw['reviewId'] ?? null;
        if (!is_string($reviewId) || $reviewId === '') {
            $this->logger->warning('Google review missing reviewId; skipping', ['raw_keys' => array_keys($raw)]);
            return null;
        }

        $starRatingRaw = $raw['starRating'] ?? null;
        $rating = is_string($starRatingRaw) ? (self::STAR_RATING_MAP[$starRatingRaw] ?? null) : null;
        if ($rating === null) {
            $this->logger->warning('Google review has unmappable starRating; skipping', [
                'review_id'  => $reviewId,
                'starRating' => $starRatingRaw,
            ]);
            return null;
        }

        $author = (string) ($raw['reviewer']['displayName'] ?? '');
        // Google omits displayName for anonymous reviewers; fall back to a
        // generic label rather than persisting an empty author field.
        if ($author === '') {
            $author = 'Google User';
        }

        $comment = $raw['comment'] ?? '';
        if (!is_string($comment)) {
            $comment = '';
        }

        $updateTimeRaw = $raw['updateTime'] ?? $raw['createTime'] ?? null;
        $externalUpdatedAt = $this->parseRfc3339($updateTimeRaw);
        if ($externalUpdatedAt === null) {
            $this->logger->warning('Google review missing/invalid updateTime; skipping', [
                'review_id'  => $reviewId,
                'updateTime' => $updateTimeRaw,
            ]);
            return null;
        }

        return new ReviewDto(
            source: $this->getSourceKey(),
            externalId: $reviewId,
            author: $author,
            reviewText: $comment,
            rating: $rating,
            ratingScale: 5,
            city: null,
            state: null,
            sourceUrl: $this->businessReviewsUrl !== '' ? $this->businessReviewsUrl : null,
            externalUpdatedAt: $externalUpdatedAt,
        );
    }

    private function parseRfc3339(mixed $raw): ?DateTimeImmutable
    {
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        try {
            return (new DateTimeImmutable($raw))->setTimezone(new DateTimeZone('UTC'));
        } catch (\Throwable) {
            return null;
        }
    }
}
