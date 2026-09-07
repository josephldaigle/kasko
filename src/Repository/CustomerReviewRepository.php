<?php

namespace Kasko\Repository;

use Kasko\Entity\CustomerReview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    // /**
    //  * @return CustomerReview[] Returns an array of CustomerReview objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CustomerReview
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
