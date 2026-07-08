<?php

namespace App\Controller;

use App\Repository\CoursRepository;
use App\Repository\CalendrierRepository;
use App\Repository\ProgressionRepository;
use App\Repository\NoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EtudiantController extends AbstractController
{
    #[Route('/etudiant/dashboard', name: 'etudiant_dashboard')]
    public function dashboard(
        CoursRepository $coursRepository,
        CalendrierRepository $calendrierRepository,
        \App\Repository\ClasseRepository $classeRepository,
        ProgressionRepository $progressionRepository
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        try {
            $user = $this->getUser();
            
            // Récupérer les cours auxquels l'étudiant est inscrit
            $cours = $coursRepository->findByUser($user);
            
            // Limiter à 5 cours pour le dashboard
            $coursLimites = array_slice($cours, 0, 5);
            
            // Progressions réelles de l'étudiant (pour calculs/affichage)
            $progressionsUser = $progressionRepository->findBy(['user' => $user]);
            $progressionsByCoursId = [];
            foreach ($progressionsUser as $progression) {
                $coursId = $progression->getCours()?->getId();
                if ($coursId !== null) {
                    $progressionsByCoursId[$coursId] = $progression;
                }
            }

            // Calcul des statistiques
            $stats = $this->calculateStats($cours, $progressionsByCoursId);

            // Activité récente : dernières progressions (updatedAt DESC)
            $recentProgressions = $progressionRepository->createQueryBuilder('p')
                ->andWhere('p.user = :user')
                ->setParameter('user', $user)
                ->orderBy('p.updatedAt', 'DESC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();

            $activiteRecente = $this->getRecentActivity($recentProgressions);

            // Cours avec progression réelle
            $coursAvecProgression = $this->getCoursAvecProgression($coursLimites, $progressionsByCoursId);

            // Récupérer les événements à venir pour l'étudiant
            $evenementsAvenir = [];
            try {
                // Utiliser le repository pour récupérer les classes de l'étudiant
                $classes = $classeRepository->findByEtudiant($user);
                
                foreach ($classes as $classe) {
                    foreach ($classe->getCours() as $coursClasse) {
                        $calendriers = $calendrierRepository->findUpcomingByCours($coursClasse);
                        foreach ($calendriers as $calendrier) {
                            $evenementsAvenir[$calendrier->getId()] = $calendrier;
                        }
                    }
                }
            } catch (\Exception $e) {
                // On continue sans événements si la requête échoue.
            }

            // Dédoublonnage par id
            $evenementsAvenir = array_values($evenementsAvenir);
            
            // Trier par date et limiter à 5
            usort($evenementsAvenir, function($a, $b) {
                return $a->getDateDebut() <=> $b->getDateDebut();
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

    #[Route('/etudiant/notes', name: 'etudiant_notes')]
    public function notes(NoteRepository $noteRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $notes = $noteRepository->findByEtudiant($user);
        $moyenneGenerale = $noteRepository->getMoyenneEtudiant($user);

        $notesParCours = [];
        foreach ($notes as $note) {
            $module = $note->getModule();
            $cours = $module?->getCours();

            if (!$cours || !$module) {
                continue;
            }

            $coursId = $cours->getId();
            $moduleId = $module->getId();

            if (!isset($notesParCours[$coursId])) {
                $notesParCours[$coursId] = [
                    'cours' => $cours,
                    'modules' => [],
                ];
            }

            if (!isset($notesParCours[$coursId]['modules'][$moduleId])) {
                $notesParCours[$coursId]['modules'][$moduleId] = [
                    'module' => $module,
                    'notes' => [],
                ];
            }

            $notesParCours[$coursId]['modules'][$moduleId]['notes'][] = $note;
        }

        // Réindexation pour un rendu Twig plus simple
        $notesParCours = array_values($notesParCours);
        foreach ($notesParCours as &$coursBlock) {
            $coursBlock['modules'] = array_values($coursBlock['modules']);
        }
        unset($coursBlock);

        return $this->render('etudiant/notes.html.twig', [
            'notesParCours' => $notesParCours,
            'moyenneGenerale' => $moyenneGenerale,
        ]);
    }

    private function calculateStats(array $cours, array $progressionsByCoursId): array
    {
        $totalCours = count($cours);
        $totalModules = 0;
        $totalChapitres = 0;
        $progressionTotale = 0.0;
        $nbCoursPourMoyenne = 0;

        foreach ($cours as $c) {
            $totalModules += $c->getModules()->count();
            foreach ($c->getModules() as $module) {
                $totalChapitres += $module->getChapitres()->count();
            }
            
            $coursId = $c->getId();
            $progression = $coursId !== null && isset($progressionsByCoursId[$coursId])
                ? ($progressionsByCoursId[$coursId]->getAvancement() ?? 0)
                : 0;

            $progressionTotale += (float) $progression;
            $nbCoursPourMoyenne++;
        }

        $progressionMoyenne = $nbCoursPourMoyenne > 0 ? round($progressionTotale / $nbCoursPourMoyenne) : 0;

        return [
            'totalCours' => $totalCours,
            'totalModules' => $totalModules,
            'totalChapitres' => $totalChapitres,
            'progressionMoyenne' => $progressionMoyenne,
        ];
    }

    private function getRecentActivity(array $recentProgressions): array
    {
        $activites = [];

        foreach ($recentProgressions as $progression) {
            $cours = $progression->getCours();
            if (!$cours) {
                continue;
            }

            $dernierChapitre = $progression->getDernierChapitre();
            $date = $progression->getUpdatedAt() ?? new \DateTimeImmutable();

            $message = $dernierChapitre
                ? 'Chapitre consulté : ' . $dernierChapitre->getTitre() . ' (' . $cours->getTitre() . ')'
                : 'Progression mise à jour : ' . $cours->getTitre();

            $activites[] = [
                'type' => 'progression_update',
                'message' => $message,
                'date' => $date,
                'icon' => $dernierChapitre ? 'bi-journal-text' : 'bi-graph-up',
            ];
        }

        return array_slice($activites, 0, 10);
    }

    private function getCoursAvecProgression(array $cours, array $progressionsByCoursId): array
    {
        $coursAvecProgression = [];
        
        foreach ($cours as $c) {
            $coursId = $c->getId();
            $progression = $coursId !== null && isset($progressionsByCoursId[$coursId])
                ? ($progressionsByCoursId[$coursId]->getAvancement() ?? 0)
                : 0;

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
