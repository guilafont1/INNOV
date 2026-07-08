<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Entity\Evaluation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EvaluationController extends AbstractController
{
    #[Route('/evaluations/cours/{id}', name: 'app_evaluations_cours', requirements: ['id' => '\d+'])]
    public function cours(Cours $cours, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        if (!$this->isUserAllowedOnCours($user, $cours)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ces évaluations.');
        }

        $evaluations = $em->getRepository(Evaluation::class)->findBy(
            ['cours' => $cours],
            ['id' => 'ASC']
        );

        return $this->render('evaluation/cours.html.twig', [
            'cours' => $cours,
            'evaluations' => $evaluations,
        ]);
    }

    private function isUserAllowedOnCours(User $user, Cours $cours): bool
    {
        if ($this->isGranted('ROLE_ADMIN_ECOLE')) {
            return true;
        }

        if ($this->isGranted('ROLE_ENSEIGNANT')) {
            if ($cours->getCreatedBy() !== null && $cours->getCreatedBy() === $user) {
                return true;
            }

            foreach ($cours->getClasses() as $classe) {
                if ($classe->getProfesseurs()->contains($user)) {
                    return true;
                }
            }
        }

        // Étudiant : accès via l'appartenance aux classes
        foreach ($cours->getClasses() as $classe) {
            if ($classe->getEtudiants()->contains($user)) {
                return true;
            }
        }

        return false;
    }
}

