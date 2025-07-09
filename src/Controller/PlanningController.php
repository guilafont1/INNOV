<?php

namespace App\Controller;

use App\Entity\Calendrier;
use App\Entity\Cours;
use App\Entity\Classe;
use App\Repository\CalendrierRepository;
use App\Repository\CoursRepository;
use App\Repository\ClasseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class PlanningController extends AbstractController
{
    #[Route('/enseignant/planning', name: 'enseignant_planning')]
    public function enseignantIndex(CalendrierRepository $calendrierRepository, CoursRepository $coursRepository, ClasseRepository $classeRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        $user = $this->getUser();
        
        // Récupérer tous les cours de l'enseignant
        $cours = $coursRepository->findByCreatedBy($user);
        
        // Récupérer tous les événements de planning pour ces cours et l'enseignant
        $planning = [];
        
        // Événements liés aux cours
        foreach ($cours as $c) {
            $calendriers = $calendrierRepository->findBy(['cours' => $c], ['dateDebut' => 'ASC']);
            foreach ($calendriers as $calendrier) {
                $planning[] = $calendrier;
            }
        }
        
        // Événements où l'enseignant est directement affecté
        $calendriersEnseignant = $calendrierRepository->findBy(['enseignant' => $user], ['dateDebut' => 'ASC']);
        foreach ($calendriersEnseignant as $calendrier) {
            // Éviter les doublons
            if (!in_array($calendrier, $planning)) {
                $planning[] = $calendrier;
            }
        }
        
        // Trier par date
        usort($planning, function($a, $b) {
            return $a->getDateDebut() <=> $b->getDateDebut();
        });

        return $this->render('enseignant/planning/index.html.twig', [
            'planning' => $planning,
            'cours' => $cours,
        ]);
    }

    #[Route('/enseignant/planning/cours/{id}', name: 'enseignant_planning_cours')]
    public function planningCours(Cours $cours, CalendrierRepository $calendrierRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        // Vérifier que l'utilisateur est le créateur du cours
        if ($cours->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir ce planning.');
        }

        $planning = $calendrierRepository->findBy(['cours' => $cours], ['dateDebut' => 'ASC']);

        return $this->render('enseignant/planning/cours.html.twig', [
            'cours' => $cours,
            'planning' => $planning,
        ]);
    }

    #[Route('/enseignant/planning/classe/{id}', name: 'enseignant_planning_classe')]
    public function planningClasse(Classe $classe, CalendrierRepository $calendrierRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        // Vérifier que l'utilisateur est professeur de cette classe
        if (!$classe->getProfesseurs()->contains($this->getUser())) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas professeur de cette classe.');
        }

        // Récupérer tous les cours de la classe
        $cours = $classe->getCours();
        
        // Récupérer tous les événements de planning pour ces cours
        $planning = [];
        foreach ($cours as $c) {
            $calendriers = $calendrierRepository->findBy(['cours' => $c], ['dateDebut' => 'ASC']);
            foreach ($calendriers as $calendrier) {
                $planning[] = $calendrier;
            }
        }
        
        // Trier par date
        usort($planning, function($a, $b) {
            return $a->getDateDebut() <=> $b->getDateDebut();
        });

        return $this->render('enseignant/planning/classe.html.twig', [
            'classe' => $classe,
            'planning' => $planning,
        ]);
    }

    #[Route('/enseignant/planning/new/{coursId}', name: 'enseignant_planning_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, CoursRepository $coursRepository, int $coursId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        $cours = $coursRepository->find($coursId);
        
        if (!$cours) {
            throw $this->createNotFoundException('Cours introuvable.');
        }

        // Vérifier que l'utilisateur est le créateur du cours
        if ($cours->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à créer un événement pour ce cours.');
        }

        $calendrier = new Calendrier();
        $calendrier->setCours($cours);

        if ($request->isMethod('POST')) {
            // Debug - Enregistrer l'ensemble de la requête
            error_log('=============== DÉBUT DEBUG CRÉATION ÉVÉNEMENT ===============');
            error_log('Méthode de requête: ' . $request->getMethod());
            error_log('Content-Type: ' . $request->headers->get('Content-Type'));
            
            // Enregistrer tous les paramètres reçus
            error_log('TOUS LES PARAMÈTRES POST: ' . print_r($request->request->all(), true));
            
            // Récupération des valeurs
            $titre = $request->request->get('titre');
            $description = $request->request->get('description');
            $type = $request->request->get('type');
            
            // ALTERNATIVE: essayer d'autres méthodes pour récupérer le type
            error_log('Type via request->get(): ' . ($type ?? 'null'));
            
            // Essayer de récupérer directement depuis $_POST
            if (isset($_POST['type'])) {
                error_log('Type via $_POST: ' . $_POST['type']);
                if (!$type) {
                    $type = $_POST['type'];
                    error_log('Type récupéré depuis $_POST');
                }
            } else {
                error_log('Type non présent dans $_POST');
            }
            
            // Essayer de récupérer via une requête SQL pour comprendre le problème
            try {
                $conn = $entityManager->getConnection();
                $lastEvent = $conn->fetchAssociative(
                    'SELECT id, type FROM calendrier ORDER BY id DESC LIMIT 1'
                );
                if ($lastEvent) {
                    error_log('Dernier événement en base - ID: ' . $lastEvent['id'] . ', Type: ' . $lastEvent['type']);
                }
            } catch (\Exception $e) {
                error_log('Erreur SQL: ' . $e->getMessage());
            }
            
            $dateDebut = $request->request->get('dateDebut');
            $dateFin = $request->request->get('dateFin');
            
            error_log('Date début reçue: ' . ($dateDebut ?? 'null'));
            error_log('Date fin reçue: ' . ($dateFin ?? 'null'));

            if ($titre && $description && $dateDebut && $dateFin) {
                $calendrier->setTitre($titre);
                $calendrier->setDescription($description);
                
                // Debug - Vérifier la valeur du type avant et après l'assignation
                error_log('Setting type to: ' . $type);
                $calendrier->setType($type);
                error_log('Type after setting: ' . $calendrier->getType());
                
                $calendrier->setDateDebut(new \DateTime($dateDebut));
                $calendrier->setDateFin(new \DateTime($dateFin));

                $entityManager->persist($calendrier);
                error_log('Avant flush - Type de l\'événement: ' . $calendrier->getType());
                $entityManager->flush();
                error_log('Après flush - Type de l\'événement: ' . $calendrier->getType());
                error_log('ID de l\'événement créé: ' . $calendrier->getId());
                
                // Récupération des informations complètes sur l'événement qui vient d'être créé
                error_log('============ VÉRIFICATION DE L\'ÉVÉNEMENT CRÉÉ ============');
                $newEventId = $calendrier->getId();
                try {
                    // Récupérer toutes les colonnes de l'événement créé
                    $conn = $entityManager->getConnection();
                    $sqlQuery = "SELECT * FROM calendrier WHERE id = :id";
                    $stmt = $conn->prepare($sqlQuery);
                    $stmt->bindValue('id', $newEventId);
                    $result = $stmt->executeQuery();
                    $eventData = $result->fetchAssociative();
                    
                    if ($eventData) {
                        error_log('Événement récupéré en base de données :');
                        foreach ($eventData as $key => $value) {
                            error_log("  $key: " . ($value !== null ? $value : 'NULL'));
                        }
                    } else {
                        error_log('Événement non trouvé en base de données !');
                    }
                } catch (\Exception $e) {
                    error_log('Erreur lors de la vérification en base de données: ' . $e->getMessage());
                }
                error_log('=============== FIN DEBUG CRÉATION ÉVÉNEMENT ===============');

                $this->addFlash('success', 'Événement ajouté avec succès.');
                return $this->redirectToRoute('enseignant_planning_cours', ['id' => $cours->getId()]);
            } else {
                $this->addFlash('error', 'Tous les champs sont requis.');
            }
        }

        return $this->render('enseignant/planning/new.html.twig', [
            'cours' => $cours,
            'calendrier' => $calendrier,
        ]);
    }

    #[Route('/enseignant/planning/edit/{id}', name: 'enseignant_planning_edit')]
    public function edit(Request $request, Calendrier $calendrier, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        // Vérifier que l'utilisateur est le créateur du cours
        if ($calendrier->getCours()->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cet événement.');
        }

        if ($request->isMethod('POST')) {
            // Debug - Enregistrer l'ensemble de la requête
            error_log('=============== DÉBUT DEBUG ÉDITION ÉVÉNEMENT ===============');
            error_log('Méthode de requête: ' . $request->getMethod());
            error_log('Content-Type: ' . $request->headers->get('Content-Type'));
            
            // Enregistrer tous les paramètres reçus
            error_log('TOUS LES PARAMÈTRES POST (ÉDITION): ' . print_r($request->request->all(), true));
            
            // Récupération des valeurs
            $titre = $request->request->get('titre');
            $description = $request->request->get('description');
            $type = $request->request->get('type');
            
            // Debug des valeurs individuelles
            error_log('Titre reçu (édition): ' . ($titre ?? 'null'));
            error_log('Type reçu (édition): ' . ($type ?? 'null') . ' (type PHP: ' . gettype($type) . ')');
            
            // S'assurer que le type est bien défini
            if (!$type) {
                $type = 'cours';  // Valeur par défaut
                error_log('Type était vide en édition, utilisation de la valeur par défaut: cours');
            }
            
            error_log('Type utilisé (édition): ' . $type);
            
            $dateDebut = $request->request->get('dateDebut');
            $dateFin = $request->request->get('dateFin');

            if ($titre && $description && $dateDebut && $dateFin) {
                $calendrier->setTitre($titre);
                $calendrier->setDescription($description);
                $calendrier->setType($type);
                $calendrier->setDateDebut(new \DateTime($dateDebut));
                $calendrier->setDateFin(new \DateTime($dateFin));

                error_log('Avant flush (édition) - Type de l\'événement: ' . $calendrier->getType());
                $entityManager->flush();
                error_log('Après flush (édition) - Type de l\'événement: ' . $calendrier->getType());
                error_log('ID de l\'événement modifié: ' . $calendrier->getId());
                error_log('=============== FIN DEBUG ÉDITION ÉVÉNEMENT ===============');

                $this->addFlash('success', 'Événement modifié avec succès.');
                return $this->redirectToRoute('enseignant_planning_cours', ['id' => $calendrier->getCours()->getId()]);
            } else {
                $this->addFlash('error', 'Tous les champs sont requis.');
            }
        }

        return $this->render('enseignant/planning/edit.html.twig', [
            'calendrier' => $calendrier,
        ]);
    }

    #[Route('/enseignant/planning/delete/{id}', name: 'enseignant_planning_delete', methods: ['POST'])]
    public function delete(Request $request, Calendrier $calendrier, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        // Vérifier que l'utilisateur est le créateur du cours
        if ($calendrier->getCours()->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cet événement.');
        }

        if ($this->isCsrfTokenValid('delete'.$calendrier->getId(), $request->request->get('_token'))) {
            $coursId = $calendrier->getCours()->getId();
            $entityManager->remove($calendrier);
            $entityManager->flush();

            $this->addFlash('success', 'Événement supprimé avec succès.');
            return $this->redirectToRoute('enseignant_planning_cours', ['id' => $coursId]);
        }

        return $this->redirectToRoute('enseignant_planning');
    }

    #[Route('/etudiant/planning', name: 'etudiant_planning')]
    public function etudiantIndex(CalendrierRepository $calendrierRepository, CoursRepository $coursRepository, ClasseRepository $classeRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        $user = $this->getUser();
        
        // Récupérer tous les cours des classes de l'étudiant
        $planning = [];
        try {
            // Solution alternative: utiliser le repository pour récupérer les classes de l'étudiant
            $classes = $classeRepository->findByEtudiant($user);
            
            foreach ($classes as $classe) {
                foreach ($classe->getCours() as $cours) {
                    $calendriers = $calendrierRepository->findBy(['cours' => $cours], ['dateDebut' => 'ASC']);
                    foreach ($calendriers as $calendrier) {
                        $planning[] = $calendrier;
                    }
                }
                
                // Ajout: récupérer aussi les événements liés directement à la classe
                $calendriersClasse = $calendrierRepository->findBy(['classe' => $classe], ['dateDebut' => 'ASC']);
                foreach ($calendriersClasse as $calendrier) {
                    $planning[] = $calendrier;
                }
            }
        } catch (\Exception $e) {
            // Log the error but continue with empty planning
            error_log('Error getting student planning: ' . $e->getMessage());
        }
        
        // Trier par date
        usort($planning, function($a, $b) {
            return $a->getDateDebut() <=> $b->getDateDebut();
        });

        return $this->render('etudiant/planning/index.html.twig', [
            'planning' => $planning,
        ]);
    }
}
