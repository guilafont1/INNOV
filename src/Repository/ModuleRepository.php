<?php

namespace App\Repository;

use App\Entity\Module;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Module>
 */
class ModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Module::class);
    }

    /**
     * Récupère les modules des cours auxquels un utilisateur est inscrit
     * @return Module[] Returns an array of Module objects
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.cours', 'c')
            ->innerJoin('c.progressions', 'p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.titre', 'ASC')
            ->addOrderBy('m.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Module[] Returns an array of Module objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Module
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
