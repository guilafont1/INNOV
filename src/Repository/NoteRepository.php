<?php

namespace App\Repository;

use App\Entity\Note;
use App\Entity\User;
use App\Entity\Module;
use App\Entity\Classe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    /**
     * Récupère les notes d'un étudiant
     */
    public function findByEtudiant(User $etudiant): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les notes d'un module
     */
    public function findByModule(Module $module): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.module = :module')
            ->setParameter('module', $module)
            ->orderBy('n.etudiant', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les notes d'un étudiant pour un module spécifique
     */
    public function findByEtudiantAndModule(User $etudiant, Module $module): ?Note
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.etudiant = :etudiant')
            ->andWhere('n.module = :module')
            ->setParameter('etudiant', $etudiant)
            ->setParameter('module', $module)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère les notes d'une classe pour un module
     */
    public function findByClasseAndModule(Classe $classe, Module $module): array
    {
        return $this->createQueryBuilder('n')
            ->join('n.etudiant', 'e')
            ->join('e.classes', 'c')
            ->andWhere('c = :classe')
            ->andWhere('n.module = :module')
            ->setParameter('classe', $classe)
            ->setParameter('module', $module)
            ->orderBy('e.email', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule la moyenne d'un étudiant
     */
    public function getMoyenneEtudiant(User $etudiant): float
    {
        $result = $this->createQueryBuilder('n')
            ->select('AVG(n.note / n.noteMax * 20) as moyenne')
            ->andWhere('n.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->getQuery()
            ->getSingleScalarResult();

        return round($result ?? 0, 2);
    }
}
