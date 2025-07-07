<?php

namespace App\Repository;

use App\Entity\Cours;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cours>
 */
class CoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cours::class);
    }

    /**
     * Récupère les cours auxquels un utilisateur est inscrit
     * @return Cours[] Returns an array of Cours objects
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.progressions', 'p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les cours pour un enseignant (incluant ceux qu'il a créés)
     * Pour l'instant retourne tous les cours car il n'y a pas de relation createdBy
     * @return Cours[] Returns an array of Cours objects
     */
    public function findForEnseignant(User $user): array
    {
        // Pour l'instant, on retourne tous les cours pour les enseignants
        // car il n'y a pas de relation directe createdBy
        return $this->findAll();
    }

//    /**
//     * @return Cours[] Returns an array of Cours objects
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

//    public function findOneBySomeField($value): ?Cours
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
