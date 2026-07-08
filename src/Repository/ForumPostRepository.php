<?php

namespace App\Repository;

use App\Entity\ForumPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ForumPost>
 */
class ForumPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumPost::class);
    }

//    /**
//     * @return ForumPost[] Returns an array of ForumPost objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ForumPost
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    /**
     * @return array<string, int>
     */
    public function countGroupedByDaySince(\DateTimeImmutable $since): array
    {
        $posts = $this->createQueryBuilder('f')
            ->where('f.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($posts as $post) {
            $day = $post->getCreatedAt()?->format('Y-m-d');
            if ($day === null) {
                continue;
            }
            $counts[$day] = ($counts[$day] ?? 0) + 1;
        }

        return $counts;
    }
}
