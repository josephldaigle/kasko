<?php

namespace Kasko\Repository;

use Kasko\Entity\FormLead;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FormLead|null find($id, $lockMode = null, $lockVersion = null)
 * @method FormLead|null findOneBy(array $criteria, array $orderBy = null)
 * @method FormLead[]    findAll()
 * @method FormLead[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormLeadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormLead::class);
    }

    // /**
    //  * @return FormLead[] Returns an array of FormLead objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FormLead
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
