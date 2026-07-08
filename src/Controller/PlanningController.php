<?php

namespace App\Controller;

use App\Entity\Calendrier;
use App\Entity\Cours;
use App\Entity\Classe;
use App\Repository\CalendrierRepository;
use App\Repository\CoursRepository;
use App\Repository\ClasseRepository;
use App\Form\CalendrierType;
use App\Entity\User;
use App\Service\PlanningPageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class PlanningController extends AbstractController
{
    #[Route('/enseignant/planning', name: 'enseignant_planning')]
    public function enseignantIndex(PlanningPageService $planningPageService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        /** @var User $user */
        $user = $this->getUser();

        return $this->render('planning/index.html.twig', $planningPageService->buildPageContext($user));
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
        $this->denyAccessUnlessGranted('ROLE_ADMIN_ECOLE');

        $cours = $coursRepository->find($coursId);
        
        if (!$cours) {
            throw $this->createNotFoundException('Cours introuvable.');
        }

        $calendrier = new Calendrier();
        $calendrier->setCours($cours);

        $form = $this->createForm(CalendrierType::class, $calendrier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($calendrier);
            $entityManager->flush();

            $this->addFlash('success', 'Événement ajouté.');

            return $this->redirectToRoute('admin_planning');
        }

        return $this->render('enseignant/planning/new.html.twig', [
            'cours' => $cours,
            'calendrier' => $calendrier,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/enseignant/planning/edit/{id}', name: 'enseignant_planning_edit')]
    public function edit(Request $request, Calendrier $calendrier, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN_ECOLE');

        $form = $this->createForm(CalendrierType::class, $calendrier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Événement modifié.');

            return $this->redirectToRoute('admin_planning');
        }

        return $this->render('enseignant/planning/edit.html.twig', [
            'calendrier' => $calendrier,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/enseignant/planning/delete/{id}', name: 'enseignant_planning_delete', methods: ['POST'])]
    public function delete(Request $request, Calendrier $calendrier, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN_ECOLE');

        if ($this->isCsrfTokenValid('delete'.$calendrier->getId(), $request->request->get('_token'))) {
            $entityManager->remove($calendrier);
            $entityManager->flush();

            $this->addFlash('success', 'Événement supprimé.');
            return $this->redirectToRoute('admin_planning');
        }

        return $this->redirectToRoute('admin_planning');
    }

    #[Route('/etudiant/planning', name: 'etudiant_planning')]
    public function etudiantIndex(PlanningPageService $planningPageService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        /** @var User $user */
        $user = $this->getUser();

        return $this->render('planning/index.html.twig', $planningPageService->buildPageContext($user));
    }
}
