<?php

declare(strict_types=1);

namespace Kasko\Services\Reviews;

/**
 * ReviewProviderInterface — contract for a single review source.
 *
 * Implementations are tagged `app.review_provider` in
 * config/services.yaml and auto-collected by SyncReviewsCommand
 * via a tagged iterator. Adding a second source (Yelp,
 * Facebook, etc.) requires only a new class implementing this
 * interface — no changes to the command, repository, entity,
 * or Twig components.
 *
 * Contract:
 *   - `getSourceKey()` returns a short, stable identifier
 *     ('google', 'yelp', ...) matched against
 *     `customer_review.source`. The sync is strictly scoped by
 *     this key: a run of the Google provider cannot touch a
 *     row whose source is 'yelp'.
 *
 *   - `fetchAll()` MUST return the complete current review set
 *     from the provider — all pages, in any order. It MUST
 *     throw on any failure (auth, HTTP, parse, partial page);
 *     a successful return means the sync may safely diff
 *     against local rows and DELETE missing externalIds. The
 *     return type is an iterable of ReviewDto so providers can
 *     page lazily via generators without buffering the whole
 *     set in memory.
 */
interface ReviewProviderInterface
{
    public function getSourceKey(): string;

    /**
     * @return iterable<ReviewDto>
     * @throws \RuntimeException on any auth/HTTP/parse failure.
     */
    public function fetchAll(): iterable;
}
