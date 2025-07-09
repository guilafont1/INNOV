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
    public function enseignantIndex(CalendrierRepository $calendrierRepository, CoursRepository $coursRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        $user = $this->getUser();
        
        // Récupérer tous les cours de l'enseignant
        $cours = $coursRepository->findByCreatedBy($user);
        
        // Récupérer tous les événements de planning pour ces cours
        $planning = [];
        foreach ($cours as $c) {
            $calendriers = $calendrierRepository->findBy(['cours' => $c], ['dateHeure' => 'ASC']);
            foreach ($calendriers as $calendrier) {
                $planning[] = $calendrier;
            }
        }
        
        // Trier par date
        usort($planning, function($a, $b) {
            return $a->getDateHeure() <=> $b->getDateHeure();
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

        $planning = $calendrierRepository->findBy(['cours' => $cours], ['dateHeure' => 'ASC']);

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
            $calendriers = $calendrierRepository->findBy(['cours' => $c], ['dateHeure' => 'ASC']);
            foreach ($calendriers as $calendrier) {
                $planning[] = $calendrier;
            }
        }
        
        // Trier par date
        usort($planning, function($a, $b) {
            return $a->getDateHeure() <=> $b->getDateHeure();
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
            $titre = $request->request->get('titre');
            $description = $request->request->get('description');
            $dateHeure = $request->request->get('dateHeure');

            if ($titre && $description && $dateHeure) {
                $calendrier->setTitre($titre);
                $calendrier->setDescription($description);
                $calendrier->setDateHeure(new \DateTime($dateHeure));

                $entityManager->persist($calendrier);
                $entityManager->flush();

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
            $titre = $request->request->get('titre');
            $description = $request->request->get('description');
            $dateHeure = $request->request->get('dateHeure');

            if ($titre && $description && $dateHeure) {
                $calendrier->setTitre($titre);
                $calendrier->setDescription($description);
                $calendrier->setDateHeure(new \DateTime($dateHeure));

                $entityManager->flush();

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
                    $calendriers = $calendrierRepository->findBy(['cours' => $cours], ['dateHeure' => 'ASC']);
                    foreach ($calendriers as $calendrier) {
                        $planning[] = $calendrier;
                    }
                }
            }
        } catch (\Exception $e) {
            // Log the error but continue with empty planning
            error_log('Error getting student planning: ' . $e->getMessage());
        }
        
        // Trier par date
        usort($planning, function($a, $b) {
            return $a->getDateHeure() <=> $b->getDateHeure();
        });

        return $this->render('etudiant/planning/index.html.twig', [
            'planning' => $planning,
        ]);
    }
}
