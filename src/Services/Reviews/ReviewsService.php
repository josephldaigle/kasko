<?php

declare(strict_types=1);

namespace Kasko\Services\Reviews;

use Kasko\Entity\CustomerReview;
use Kasko\Repository\CustomerReviewRepository;

/**
 * ReviewsService — read-only façade over CustomerReviewRepository.
 *
 * Any controller, listener, or Twig data compiler that needs
 * reviews should depend on this class rather than hitting the
 * repository directly. Public methods are the contract; the
 * repository can change its query shapes without affecting
 * callers.
 *
 * Sync writes bypass this service and go directly through
 * SyncReviewsCommand → EntityManager so that transactional
 * semantics stay in one place.
 */
class ReviewsService
{
    public function __construct(
        private readonly CustomerReviewRepository $repository,
    ) {
    }

    /**
     * Every review, newest-provider-update first.
     *
     * @return CustomerReview[]
     */
    public function all(): array
    {
        return $this->repository->findLatest();
    }

    /**
     * @return CustomerReview[]
     */
    public function limit(int $max): array
    {
        return $this->repository->findLatest($max);
    }

    /**
     * @return CustomerReview[]
     */
    public function byProvider(string $source, ?int $limit = null): array
    {
        return $this->repository->findBySource($source, $limit);
    }

    /**
     * @return CustomerReview[]
     */
    public function topRated(?int $limit = null): array
    {
        return $this->repository->findTopRated($limit);
    }

    /**
     * @return CustomerReview[]
     */
    public function latest(?int $limit = null): array
    {
        return $this->repository->findLatest($limit);
    }
}
