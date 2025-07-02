<?php

namespace App\Controller;

use App\Entity\Chapitre;
use App\Entity\Module;
use App\Form\ChapitreType;
use App\Repository\ChapitreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChapitreController extends AbstractController
{
    #[Route('/chapitre', name: 'app_chapitre_index')]
    public function index(ChapitreRepository $chapitreRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');
        
        $chapitres = $chapitreRepository->findAll();
        
        return $this->render('chapitre/index.html.twig', [
            'chapitres' => $chapitres,
        ]);
    }

    #[Route('/chapitre/{id}', name: 'app_chapitre_show', requirements: ['id' => '\d+'])]
    public function show(Chapitre $chapitre): Response
    {
        return $this->render('chapitre/show.html.twig', [
            'chapitre' => $chapitre,
        ]);
    }

    #[Route('/module/{id}/chapitre/new', name: 'app_chapitre_new')]
    public function addChapitre(Request $request, Module $module, EntityManagerInterface $em): Response
    {
        if (!in_array('ROLE_ENSEIGNANT', $this->getUser()->getRoles())) {
            $this->addFlash('error', 'Accès réservé aux enseignants.');
            throw $this->createAccessDeniedException('Accès réservé aux enseignants.');
        }

        $chapitre = new Chapitre();
        $chapitre->setModule($module);

        $form = $this->createForm(ChapitreType::class, $chapitre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->persist($chapitre);
                $em->flush();

                $this->addFlash('success', 'Chapitre ajouté avec succès.');
                return $this->redirectToRoute('app_cours_show', ['id' => $module->getCours()->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du chapitre.');
            }
        }

        return $this->render('chapitre/new.html.twig', [
            'form' => $form->createView(),
            'module' => $module
        ]);
    }

    #[Route('/chapitre/{id}/edit', name: 'app_chapitre_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Chapitre $chapitre, EntityManagerInterface $em): Response
    {
        if (!in_array('ROLE_ENSEIGNANT', $this->getUser()->getRoles())) {
            $this->addFlash('error', 'Accès réservé aux enseignants.');
            throw $this->createAccessDeniedException('Accès réservé aux enseignants.');
        }

        $form = $this->createForm(ChapitreType::class, $chapitre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();

                $this->addFlash('success', 'Chapitre modifié avec succès.');
                return $this->redirectToRoute('app_chapitre_show', ['id' => $chapitre->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification du chapitre.');
            }
        }

        return $this->render('chapitre/edit.html.twig', [
            'chapitre' => $chapitre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/chapitre/{id}/delete', name: 'app_chapitre_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Chapitre $chapitre, EntityManagerInterface $em): Response
    {
        if (!in_array('ROLE_ENSEIGNANT', $this->getUser()->getRoles())) {
            $this->addFlash('error', 'Accès réservé aux enseignants.');
            throw $this->createAccessDeniedException('Accès réservé aux enseignants.');
        }

        if ($this->isCsrfTokenValid('delete'.$chapitre->getId(), $request->request->get('_token'))) {
            try {
                $moduleId = $chapitre->getModule()->getCours()->getId();
                $em->remove($chapitre);
                $em->flush();

                $this->addFlash('success', 'Chapitre supprimé avec succès.');
                return $this->redirectToRoute('app_cours_show', ['id' => $moduleId]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression du chapitre.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_chapitre_show', ['id' => $chapitre->getId()]);
    }
}