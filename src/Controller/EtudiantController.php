<?php

namespace App\Controller;

use App\Repository\CoursRepository;
use App\Repository\CalendrierRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EtudiantController extends AbstractController
{
    #[Route('/etudiant/dashboard', name: 'etudiant_dashboard')]
    public function dashboard(CoursRepository $coursRepository, CalendrierRepository $calendrierRepository, \App\Repository\ClasseRepository $classeRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        try {
            $user = $this->getUser();
            
            // Récupérer les cours auxquels l'étudiant est inscrit
            $cours = $coursRepository->findByUser($user);
            
            // Limiter à 5 cours pour le dashboard
            $coursLimites = array_slice($cours, 0, 5);
            
            // Calcul des statistiques
            $stats = $this->calculateStats($cours, $user);
            
            // Activité récente (simulée pour l'exemple)
            $activiteRecente = $this->getRecentActivity($cours, $user);
            
            // Cours récents avec progression
            $coursAvecProgression = $this->getCoursAvecProgression($coursLimites, $user);

            // Récupérer les événements à venir pour l'étudiant
            $evenementsAvenir = [];
            try {
                // Utiliser le repository pour récupérer les classes de l'étudiant
                $classes = $classeRepository->findByEtudiant($user);
                
                foreach ($classes as $classe) {
                    foreach ($classe->getCours() as $cours) {
                        $calendriers = $calendrierRepository->findUpcomingByCours($cours);
                        foreach ($calendriers as $calendrier) {
                            $evenementsAvenir[] = $calendrier;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log the error but continue without events
                error_log('Error getting upcoming events: ' . $e->getMessage());
            }
            
            // Trier par date et limiter à 5
            usort($evenementsAvenir, function($a, $b) {
                return $a->getDateHeure() <=> $b->getDateHeure();
            });
            $evenementsAvenir = array_slice($evenementsAvenir, 0, 5);

            return $this->render('etudiant/dashboard.html.twig', [
                'cours' => $coursAvecProgression,
                'totalCours' => count($cours),
                'stats' => $stats,
                'activiteRecente' => $activiteRecente,
                'evenementsAvenir' => $evenementsAvenir,
            ]);
        } catch (\Exception $e) {
            // Log l'erreur pour le debugging
            error_log('Erreur dashboard étudiant: ' . $e->getMessage());
            
            $this->addFlash('error', 'Erreur lors du chargement du tableau de bord.');
            return $this->render('etudiant/dashboard.html.twig', [
                'cours' => [],
                'totalCours' => 0,
                'stats' => [
                    'totalCours' => 0,
                    'totalModules' => 0,
                    'totalChapitres' => 0,
                    'progressionMoyenne' => 0,
                ],
                'activiteRecente' => [],
                'evenementsAvenir' => [],
            ]);
        }
    }

    private function calculateStats(array $cours, $user): array
    {
        $totalCours = count($cours);
        $totalModules = 0;
        $totalChapitres = 0;
        $progressionTotale = 0;
        $nbProgressions = 0;

        foreach ($cours as $c) {
            $totalModules += $c->getModules()->count();
            foreach ($c->getModules() as $module) {
                $totalChapitres += $module->getChapitres()->count();
            }
            
            // Calculer la progression pour ce cours (simulée)
            $progression = rand(0, 100);
            $progressionTotale += $progression;
            $nbProgressions++;
        }

        $progressionMoyenne = $nbProgressions > 0 ? round($progressionTotale / $nbProgressions) : 0;

        return [
            'totalCours' => $totalCours,
            'totalModules' => $totalModules,
            'totalChapitres' => $totalChapitres,
            'progressionMoyenne' => $progressionMoyenne,
        ];
    }

    private function getRecentActivity(array $cours, $user): array
    {
        $activites = [];
        
        foreach ($cours as $c) {
            // Simuler de l'activité récente
            if (rand(0, 1)) {
                $activites[] = [
                    'type' => 'cours_started',
                    'message' => 'Cours commencé: ' . $c->getTitre(),
                    'date' => new \DateTimeImmutable('-' . rand(1, 7) . ' days'),
                    'icon' => '📚',
                ];
            }
            
            if (rand(0, 1)) {
                $activites[] = [
                    'type' => 'module_completed',
                    'message' => 'Module terminé dans: ' . $c->getTitre(),
                    'date' => new \DateTimeImmutable('-' . rand(1, 5) . ' days'),
                    'icon' => '✅',
                ];
            }
        }
        
        // Trier par date décroissante
        usort($activites, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });
        
        return array_slice($activites, 0, 10);
    }

    private function getCoursAvecProgression(array $cours, $user): array
    {
        $coursAvecProgression = [];
        
        foreach ($cours as $c) {
            $progression = rand(0, 100); // Simuler la progression
            $coursAvecProgression[] = [
                'cours' => $c,
                'progression' => $progression,
                'statut' => $progression < 30 ? 'débutant' : ($progression < 70 ? 'en cours' : 'avancé'),
                'couleur' => $progression < 30 ? 'danger' : ($progression < 70 ? 'warning' : 'success'),
            ];
        }
        
        return $coursAvecProgression;
    }
}
