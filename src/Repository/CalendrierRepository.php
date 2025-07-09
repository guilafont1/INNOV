<?php

namespace App\Repository;

use App\Entity\Calendrier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Calendrier>
 */
class CalendrierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Calendrier::class);
    }

    /**
     * Récupère les événements à venir pour un cours
     * @return Calendrier[] Returns an array of Calendrier objects
     */
    public function findUpcomingByCours($cours): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.cours = :cours')
            ->andWhere('c.dateDebut >= :now')
            ->setParameter('cours', $cours)
            ->setParameter('now', new \DateTime())
            ->orderBy('c.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les événements pour un enseignant
     * @return Calendrier[] Returns an array of Calendrier objects
     */
    public function findByEnseignant($enseignant): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.cours', 'cours')
            ->where('c.enseignant = :enseignant OR cours.createdBy = :enseignant')
            ->setParameter('enseignant', $enseignant)
            ->orderBy('c.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Récupère les événements dans une période avec filtres optionnels
     * @return Calendrier[] Returns an array of Calendrier objects
     */
    public function findByDateRange(\DateTime $dateStart, \DateTime $dateEnd, ?string $type = null, ?int $classeId = null, ?int $enseignantId = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.dateDebut >= :dateStart')
            ->andWhere('c.dateDebut <= :dateEnd')
            ->setParameter('dateStart', $dateStart->format('Y-m-d 00:00:00'))
            ->setParameter('dateEnd', $dateEnd->format('Y-m-d 23:59:59'))
            ->orderBy('c.dateDebut', 'ASC');
        
        if ($type) {
            $qb->andWhere('c.type = :type')
               ->setParameter('type', $type);
        }
        
        if ($classeId) {
            $qb->andWhere('c.classe = :classeId')
               ->setParameter('classeId', $classeId);
        }
        
        if ($enseignantId) {
            $qb->andWhere('c.enseignant = :enseignantId')
               ->setParameter('enseignantId', $enseignantId);
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les événements à venir pour un enseignant
     * @return Calendrier[] Returns an array of Calendrier objects
     */
    public function findUpcomingByEnseignant($enseignant): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.cours', 'cours')
            ->andWhere('cours.createdBy = :enseignant')
            ->andWhere('c.dateHeure >= :now')
            ->setParameter('enseignant', $enseignant)
            ->setParameter('now', new \DateTime())
            ->orderBy('c.dateHeure', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Calendrier[] Returns an array of Calendrier objects
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

    //    public function findOneBySomeField($value): ?Calendrier
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
