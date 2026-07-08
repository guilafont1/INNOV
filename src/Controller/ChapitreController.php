<?php

namespace App\Controller;

use App\Entity\Chapitre;
use App\Entity\Module;
use App\Entity\Progression;
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
        try {
            $user = $this->getUser();
            
            // Si l'utilisateur est admin, récupérer tous les chapitres
            if ($this->isGranted('ROLE_ADMIN_ECOLE')) {
                $chapitres = $chapitreRepository->findAll();
            } else {
                // Sinon, récupérer uniquement les chapitres des cours de l'utilisateur connecté
                $chapitres = $chapitreRepository->findByUser($user);
            }
            
            if (empty($chapitres)) {
                $message = $this->isGranted('ROLE_ADMIN_ECOLE') 
                    ? 'Aucun chapitre disponible pour le moment.'
                    : 'Vous n\'avez accès à aucun chapitre pour le moment.';
                $this->addFlash('info', $message);
            }
            
            return $this->render('chapitre/index.html.twig', [
                'chapitres' => $chapitres,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du chargement des chapitres.');
            return $this->render('chapitre/index.html.twig', ['chapitres' => []]);
        }
    }

    #[Route('/chapitre/{id}', name: 'app_chapitre_show', requirements: ['id' => '\d+'])]
    public function show(Chapitre $chapitre, ChapitreRepository $chapitreRepository, EntityManagerInterface $em): Response
    {
        // Si l'utilisateur n'est pas admin, vérifier qu'il a accès au chapitre
        if (!$this->isGranted('ROLE_ADMIN_ECOLE')) {
            $user = $this->getUser();
            $userChapitres = $chapitreRepository->findByUser($user);
            
            $hasAccess = false;
            foreach ($userChapitres as $userChapitre) {
                if ($userChapitre->getId() === $chapitre->getId()) {
                    $hasAccess = true;
                    break;
                }
            }
            
            if (!$hasAccess) {
                $this->addFlash('error', 'Vous n\'avez pas accès à ce chapitre.');
                return $this->redirectToRoute('app_chapitre_index');
            }
        }

        // Suivi de progression réel : un étudiant met à jour sa progression quand il consulte un chapitre.
        if ($this->isGranted('ROLE_ETUDIANT')) {
            $user = $this->getUser();
            $module = $chapitre->getModule();
            $cours = $module?->getCours();

            if ($user && $cours) {
                $totalChapitres = 0;
                foreach ($cours->getModules() as $m) {
                    $totalChapitres += $m->getChapitres()->count();
                }

                /** @var Progression|null $progression */
                $progression = $em->getRepository(Progression::class)->findOneBy([
                    'user' => $user,
                    'cours' => $cours,
                ]);

                if (!$progression) {
                    $progression = new Progression();
                    $progression->setUser($user);
                    $progression->setCours($cours);
                }

                $progression->addChapitreConsulte($chapitre);
                $progression->setDernierChapitre($chapitre);

                $chapitresConsultees = $progression->getChapitresConsultees()->count();
                $avancement = $totalChapitres > 0 ? ($chapitresConsultees / $totalChapitres) * 100 : 0;

                $progression->setAvancement(round($avancement, 2));
                $progression->setUpdatedAt(new \DateTimeImmutable());

                $em->persist($progression);
                $em->flush();
            }
        }
        
        return $this->render('chapitre/show.html.twig', [
            'chapitre' => $chapitre,
        ]);
    }

    #[Route('/chapitre/new', name: 'app_chapitre_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        $moduleId = $request->query->get('moduleId');
        if (!$moduleId) {
            $this->addFlash('error', 'Module non spécifié.');
            return $this->redirectToRoute('enseignant_cours');
        }

        $module = $em->getRepository(Module::class)->find($moduleId);
        if (!$module) {
            $this->addFlash('error', 'Module introuvable.');
            return $this->redirectToRoute('enseignant_cours');
        }

        $chapitre = new Chapitre();
        $chapitre->setModule($module);

        $form = $this->createForm(ChapitreType::class, $chapitre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->persist($chapitre);
                $em->flush();

                $this->addFlash('success', 'Chapitre ajouté.');
                
                // Rediriger vers la configuration du cours pour continuer le processus
                return $this->redirectToRoute('app_cours_setup', ['id' => $module->getCours()->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du chapitre.');
            }
        }

        return $this->render('chapitre/new.html.twig', [
            'form' => $form->createView(),
            'module' => $module
        ]);
    }

    #[Route('/module/{id}/chapitre/new', name: 'app_chapitre_new_from_module')]
    public function addChapitre(Request $request, Module $module, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        $chapitre = new Chapitre();
        $chapitre->setModule($module);

        $form = $this->createForm(ChapitreType::class, $chapitre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->persist($chapitre);
                $em->flush();

                $this->addFlash('success', 'Chapitre ajouté.');
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
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        $form = $this->createForm(ChapitreType::class, $chapitre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();

                $this->addFlash('success', 'Chapitre modifié.');
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
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        if ($this->isCsrfTokenValid('delete'.$chapitre->getId(), $request->request->get('_token'))) {
            try {
                $moduleId = $chapitre->getModule()->getCours()->getId();
                $em->remove($chapitre);
                $em->flush();

                $this->addFlash('success', 'Chapitre supprimé.');
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