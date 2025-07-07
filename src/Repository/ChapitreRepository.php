<?php

namespace App\Repository;

use App\Entity\Chapitre;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Chapitre>
 */
class ChapitreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chapitre::class);
    }

    /**
     * Récupère les chapitres des cours auxquels un utilisateur est inscrit
     * @return Chapitre[] Returns an array of Chapitre objects
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('ch')
            ->innerJoin('ch.module', 'm')
            ->innerJoin('m.cours', 'c')
            ->innerJoin('c.progressions', 'p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.titre', 'ASC')
            ->addOrderBy('m.titre', 'ASC')
            ->addOrderBy('ch.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Chapitre[] Returns an array of Chapitre objects
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

    //    public function findOneBySomeField($value): ?Chapitre
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
