<?php

namespace App\Repository;

use App\Entity\CategoryModerator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategoryModerator>
 *
 * @method CategoryModerator|null find($id, $lockMode = null, $lockVersion = null)
 * @method CategoryModerator|null findOneBy(array $criteria, array $orderBy = null)
 * @method CategoryModerator[]    findAll()
 * @method CategoryModerator[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryModeratorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoryModerator::class);
    }

//    /**
//     * @return CategoryModerator[] Returns an array of CategoryModerator objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CategoryModerator
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
