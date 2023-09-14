<?php

namespace App\Repository;

use App\Entity\Poll;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Poll>
 *
 * @method Poll|null find($id, $lockMode = null, $lockVersion = null)
 * @method Poll|null findOneBy(array $criteria, array $orderBy = null)
 * @method Poll[]    findAll()
 * @method Poll[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PollRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Poll::class);
    }

//    /**
//     * @return Poll[] Returns an array of Poll objects
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

//    public function findOneBySomeField($value): ?Poll
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function calculateChoicePercentages($pollId)
    {
        return $this->createQueryBuilder('p')
            ->select('c.content AS choice_content, COUNT(pa.id) AS vote_count')
            ->leftJoin('p.choice', 'c')
            ->leftJoin('c.pollAnswers', 'pa')
            ->andWhere('p.id = :pollId')
            ->andWhere('p.closed = :closed')
            ->setParameter('pollId', $pollId)
            ->setParameter('closed', true)
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();
    }
}
