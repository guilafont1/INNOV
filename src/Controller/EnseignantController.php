<?php

namespace App\Controller;

use App\Repository\CoursRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EnseignantController extends AbstractController
{
    #[Route('/enseignant/cours', name: 'enseignant_cours')]
    public function mesCours(CoursRepository $coursRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        try {
            $user = $this->getUser();
            
            // Si l'utilisateur est admin, récupérer tous les cours
            if ($this->isGranted('ROLE_ADMIN')) {
                $cours = $coursRepository->findAll();
            } else {
                // Sinon, récupérer les cours auxquels l'enseignant a accès
                $cours = $coursRepository->findByUser($user);
            }

            if (empty($cours)) {
                $message = $this->isGranted('ROLE_ADMIN') 
                    ? 'Aucun cours disponible pour le moment.'
                    : 'Vous n\'avez accès à aucun cours pour le moment.';
                $this->addFlash('info', $message);
            }

            return $this->render('enseignant/cours.html.twig', [
                'cours' => $cours,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du chargement des cours.');
            return $this->redirectToRoute('enseignant_dashboard');
        }
    }
    
    #[Route('/enseignant/dashboard', name: 'enseignant_dashboard')]
    public function dashboard(CoursRepository $coursRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        try {
            $user = $this->getUser();
            
            // Si l'utilisateur est admin, récupérer tous les cours
            if ($this->isGranted('ROLE_ADMIN')) {
                $cours = $coursRepository->findAll();
            } else {
                // Sinon, récupérer les cours auxquels l'enseignant a accès
                $cours = $coursRepository->findByUser($user);
            }

            $this->addFlash('info', 'Bienvenue sur votre tableau de bord enseignant.');

            return $this->render('enseignant/dashboard.html.twig', [
                'cours' => $cours,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du chargement du tableau de bord.');
            return $this->render('enseignant/dashboard.html.twig', [
                'cours' => [],
            ]);
        }
    }

}