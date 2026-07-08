<?php

namespace App\Repository;

use App\Entity\Calendrier;
use App\Entity\User;
use App\Security\UserRole;
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
     * Lieux distincts déjà utilisés (pour datalist).
     *
     * @return string[]
     */
    public function findDistinctLieux(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('DISTINCT c.lieu')
            ->andWhere('c.lieu IS NOT NULL')
            ->andWhere("c.lieu != ''")
            ->orderBy('c.lieu', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row) => $row['lieu'], $rows);
    }

    /**
     * Événements visibles pour un utilisateur selon son rôle, avec filtres optionnels.
     *
     * @param string[] $types
     * @param int[]    $classeIds
     * @param int[]    $enseignantIds
     * @param int[]    $coursIds
     *
     * @return Calendrier[]
     */
    public function findForUserScope(
        User $user,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        array $types = [],
        array $classeIds = [],
        array $enseignantIds = [],
        array $coursIds = [],
    ): array {
        $roles = $user->getRoles();
        $isSchoolAdmin = UserRole::isSchoolAdmin($roles);
        $isEnseignant = in_array(UserRole::ENSEIGNANT, $roles, true);

        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.cours', 'cours')->addSelect('cours')
            ->leftJoin('c.classe', 'classe')->addSelect('classe')
            ->leftJoin('c.enseignant', 'enseignant')->addSelect('enseignant')
            ->leftJoin('cours.createdBy', 'coursCreator')->addSelect('coursCreator')
            ->andWhere('c.dateDebut < :rangeEnd')
            ->andWhere('COALESCE(c.dateFin, DATE_ADD(c.dateDebut, 2, \'HOUR\')) > :rangeStart')
            ->setParameter('rangeStart', $start)
            ->setParameter('rangeEnd', $end)
            ->orderBy('c.dateDebut', 'ASC');

        if ($isSchoolAdmin) {
            // Pas de restriction de scope.
        } elseif ($isEnseignant) {
            $qb
                ->leftJoin('classe.professeurs', 'classeProf')
                ->andWhere(
                    $qb->expr()->orX(
                        'c.enseignant = :scopeUser',
                        'cours.createdBy = :scopeUser',
                        'classeProf = :scopeUser'
                    )
                )
                ->setParameter('scopeUser', $user);
        } else {
            $qb
                ->leftJoin('classe.etudiants', 'classeEtudiant')
                ->leftJoin('cours.classes', 'coursClasse')
                ->leftJoin('coursClasse.etudiants', 'coursClasseEtudiant')
                ->andWhere(
                    $qb->expr()->orX(
                        'classeEtudiant = :scopeUser',
                        'coursClasseEtudiant = :scopeUser'
                    )
                )
                ->setParameter('scopeUser', $user);
        }

        if ($types !== []) {
            $qb->andWhere('c.type IN (:types)')->setParameter('types', $types);
        }

        if ($classeIds !== []) {
            $qb->andWhere('classe.id IN (:classeIds)')->setParameter('classeIds', $classeIds);
        }

        if ($enseignantIds !== []) {
            $qb->andWhere('enseignant.id IN (:enseignantIds)')->setParameter('enseignantIds', $enseignantIds);
        }

        if ($coursIds !== []) {
            $qb->andWhere('cours.id IN (:coursIds)')->setParameter('coursIds', $coursIds);
        }

        $results = $qb->getQuery()->getResult();

        $unique = [];
        foreach ($results as $event) {
            $unique[$event->getId()] = $event;
        }

        return array_values($unique);
    }

    public function findOneInUserScope(User $user, int $id): ?Calendrier
    {
        $events = $this->findForUserScope(
            $user,
            new \DateTime('-5 years'),
            new \DateTime('+5 years'),
        );

        foreach ($events as $event) {
            if ($event->getId() === $id) {
                return $event;
            }
        }

        return null;
    }

    /**
     * Détecte les conflits horaires pour une classe ou un enseignant.
     *
     * @return Calendrier[]
     */
    public function findConflicts(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?int $classeId = null,
        ?int $enseignantId = null,
        ?int $excludeId = null,
    ): array {
        if ($classeId === null && $enseignantId === null) {
            return [];
        }

        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.cours', 'cours')->addSelect('cours')
            ->leftJoin('c.classe', 'classe')->addSelect('classe')
            ->leftJoin('c.enseignant', 'enseignant')->addSelect('enseignant')
            ->andWhere('c.dateDebut < :conflictEnd')
            ->andWhere('COALESCE(c.dateFin, DATE_ADD(c.dateDebut, 2, \'HOUR\')) > :conflictStart')
            ->setParameter('conflictStart', $start)
            ->setParameter('conflictEnd', $end);

        if ($excludeId !== null) {
            $qb->andWhere('c.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        $conditions = [];
        if ($classeId !== null) {
            $conditions[] = 'classe.id = :classeId';
            $qb->setParameter('classeId', $classeId);
        }
        if ($enseignantId !== null) {
            $conditions[] = 'enseignant.id = :enseignantId';
            $qb->setParameter('enseignantId', $enseignantId);
        }

        if (count($conditions) === 2) {
            $qb->andWhere($qb->expr()->orX(...$conditions));
        } else {
            $qb->andWhere($conditions[0]);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Calendrier[]
     */
    public function findForExport(
        User $user,
        ?\DateTimeInterface $start = null,
        ?\DateTimeInterface $end = null,
    ): array {
        $rangeStart = $start ?? new \DateTime('-1 year');
        $rangeEnd = $end ?? new \DateTime('+1 year');

        return $this->findForUserScope($user, $rangeStart, $rangeEnd);
    }

    /**
     * Récupère les événements à venir pour un enseignant
     *
     * @return Calendrier[]
     */
    public function findUpcomingByEnseignant($enseignant): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.cours', 'cours')
            ->andWhere('cours.createdBy = :enseignant')
            ->andWhere('c.dateDebut >= :now')
            ->setParameter('enseignant', $enseignant)
            ->setParameter('now', new \DateTime())
            ->orderBy('c.dateDebut', 'ASC')
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
