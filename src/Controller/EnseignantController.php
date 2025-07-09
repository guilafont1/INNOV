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
            
            // Pour l'instant, récupérer tous les cours car la relation createdBy n'est pas configurée
            $cours = $coursRepository->findAll();

            if (empty($cours)) {
                $message = $this->isGranted('ROLE_ADMIN') 
                    ? 'Aucun cours disponible pour le moment.'
                    : 'Aucun cours disponible pour le moment.';
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
            
            // Pour l'instant, récupérer tous les cours car la relation createdBy n'est pas configurée
            $cours = $coursRepository->findAll();

            // Vérifier si $cours est bien un tableau
            if (!is_array($cours)) {
                $cours = [];
            }

            // Calcul des statistiques
            $stats = $this->calculateStats($cours);
            
            // Limiter à 5 cours pour le dashboard
            $coursLimites = array_slice($cours, 0, 5);
            
            // Activité récente (simulée pour l'exemple)
            $activiteRecente = $this->getRecentActivity($cours);

            return $this->render('enseignant/dashboard.html.twig', [
                'cours' => $coursLimites,
                'totalCours' => count($cours),
                'stats' => $stats,
                'activiteRecente' => $activiteRecente,
            ]);
        } catch (\Exception $e) {
            // Log l'erreur pour le debugging
            error_log('Erreur dashboard: ' . $e->getMessage());
            
            $this->addFlash('error', 'Erreur lors du chargement du tableau de bord.');
            return $this->render('enseignant/dashboard.html.twig', [
                'cours' => [],
                'totalCours' => 0,
                'stats' => [
                    'totalCours' => 0,
                    'totalModules' => 0,
                    'totalChapitres' => 0,
                    'totalEtudiants' => 0,
                ],
                'activiteRecente' => [],
            ]);
        }
    }

    private function calculateStats(array $cours): array
    {
        $totalCours = count($cours);
        $totalModules = 0;
        $totalChapitres = 0;
        $totalEtudiants = 0;

        foreach ($cours as $c) {
            $totalModules += $c->getModules()->count();
            foreach ($c->getModules() as $module) {
                $totalChapitres += $module->getChapitres()->count();
            }
            $totalEtudiants += $c->getProgressions()->count();
        }

        return [
            'totalCours' => $totalCours,
            'totalModules' => $totalModules,
            'totalChapitres' => $totalChapitres,
            'totalEtudiants' => $totalEtudiants,
        ];
    }

    private function getRecentActivity(array $cours): array
    {
        $activites = [];
        
        foreach ($cours as $c) {
            if ($c->getCreatedAt() > new \DateTimeImmutable('-7 days')) {
                $activites[] = [
                    'type' => 'cours_created',
                    'message' => 'Nouveau cours créé: ' . $c->getTitre(),
                    'date' => $c->getCreatedAt(),
                    'icon' => '📚',
                ];
            }
        }
        
        // Trier par date décroissante
        usort($activites, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });
        
        return array_slice($activites, 0, 10);
    }

}