<?php

declare(strict_types=1);

namespace Kasko\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Kasko\Entity\CustomerReview;

/**
 * @method CustomerReview|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomerReview|null findOneBy(array $criteria, array $orderBy = null)
 * @method CustomerReview[]    findAll()
 * @method CustomerReview[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerReview::class);
    }

    /**
     * Fetch every review for one provider, keyed by externalId.
     * The sync command uses this to drive its diff against the
     * provider's current response — presence/absence of a key is
     * the authoritative insert/update/delete signal.
     *
     * @return array<string, CustomerReview>
     */
    public function findBySourceKeyedByExternalId(string $source): array
    {
        /** @var CustomerReview[] $rows */
        $rows = $this->createQueryBuilder('r')
            ->andWhere('r.source = :source')
            ->andWhere('r.externalId IS NOT NULL')
            ->setParameter('source', $source)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($rows as $row) {
            $extId = $row->getExternalId();
            if ($extId !== null) {
                $map[$extId] = $row;
            }
        }
        return $map;
    }

    /**
     * Content-keyed lookup for a manually-seeded row. Used by
     * `app:reviews:import` to upsert reviews that carry no
     * provider-side externalId (and are therefore invisible to
     * `findBySourceKeyedByExternalId`, which filters IS NOT NULL).
     * The (source, reviewer_name, review_text) tuple is a stable
     * match key for curated seed content.
     */
    public function findSeedByContent(string $source, string $reviewerName, string $reviewText): ?CustomerReview
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.source = :source')
            ->andWhere('r.externalId IS NULL')
            ->andWhere('r.reviewerName = :name')
            ->andWhere('r.reviewText = :text')
            ->setParameter('source', $source)
            ->setParameter('name', $reviewerName)
            ->setParameter('text', $reviewText)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return CustomerReview[]
     */
    public function findBySource(string $source, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.source = :source')
            ->setParameter('source', $source)
            ->orderBy('r.externalUpdatedAt', 'DESC')
            ->addOrderBy('r.id', 'DESC');
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * Reviews ordered by rating (highest first), then recency.
     *
     * @return CustomerReview[]
     */
    public function findTopRated(?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.reviewScore', 'DESC')
            ->addOrderBy('r.externalUpdatedAt', 'DESC')
            ->addOrderBy('r.id', 'DESC');
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * Reviews ordered by provider update time (newest first),
     * falling back to id for pre-sync rows that lack a provider
     * timestamp.
     *
     * @return CustomerReview[]
     */
    public function findLatest(?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.externalUpdatedAt', 'DESC')
            ->addOrderBy('r.id', 'DESC');
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        return $qb->getQuery()->getResult();
    }
}
