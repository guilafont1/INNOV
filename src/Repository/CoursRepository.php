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
            ->innerJoin('c.classes', 'cl')
            ->innerJoin('cl.etudiants', 'e')
            ->andWhere('e = :user')
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
        $coursCreeParEnseignant = $this->createQueryBuilder('c')
            ->andWhere('c.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('c.titre', 'ASC')
            ->getQuery()
            ->getResult();

        $coursDesClassesEnseignees = $this->createQueryBuilder('c')
            ->innerJoin('c.classes', 'cl')
            ->innerJoin('cl.professeurs', 'p')
            ->andWhere('p = :user')
            ->setParameter('user', $user)
            ->orderBy('c.titre', 'ASC')
            ->getQuery()
            ->getResult();

        // Dédoublonnage par id
        $byId = [];
        foreach (array_merge($coursCreeParEnseignant, $coursDesClassesEnseignees) as $cours) {
            if ($cours->getId() !== null) {
                $byId[$cours->getId()] = $cours;
            }
        }

        return array_values($byId);
    }

    /**
     * Récupère les cours créés par un utilisateur
     * @return Cours[] Returns an array of Cours objects
     */
    public function findByCreatedBy(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('c.titre', 'ASC')
            ->getQuery()
            ->getResult();
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
