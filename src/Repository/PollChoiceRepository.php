<?php

namespace App\Repository;

use App\Entity\PollChoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PollChoice>
 *
 * @method PollChoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method PollChoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method PollChoice[]    findAll()
 * @method PollChoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PollChoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PollChoice::class);
    }

//    /**
//     * @return PollChoice[] Returns an array of PollChoice objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PollChoice
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
