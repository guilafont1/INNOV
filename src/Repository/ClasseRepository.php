<?php

namespace App\Repository;

use App\Entity\Classe;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Classe>
 */
class ClasseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Classe::class);
    }

    /**
     * Récupère les classes où l'utilisateur est professeur
     * @return Classe[] Returns an array of Classe objects
     */
    public function findByProfesseur(User $professeur): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.professeurs', 'p')
            ->andWhere('p = :professeur')
            ->setParameter('professeur', $professeur)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les classes où l'utilisateur est étudiant
     * @return Classe[] Returns an array of Classe objects
     */
    public function findByEtudiant(User $etudiant): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.etudiants', 'e')
            ->andWhere('e = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les classes avec le nombre d'étudiants et de professeurs
     * @return array
     */
    public function findAllWithCounts(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.etudiants', 'e')
            ->leftJoin('c.professeurs', 'p')
            ->addSelect('COUNT(DISTINCT e.id) as nbEtudiants')
            ->addSelect('COUNT(DISTINCT p.id) as nbProfesseurs')
            ->groupBy('c.id')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Classe[] Returns an array of Classe objects
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

//    public function findOneBySomeField($value): ?Classe
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
