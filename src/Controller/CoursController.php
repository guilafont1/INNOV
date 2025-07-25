<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CoursRepository;
use App\Entity\Cours;
use App\Form\CoursType;
use Symfony\Component\Security\Core\Security;

final class CoursController extends AbstractController
{
    #[Route('/cours', name: 'app_cours')]
    public function index(CoursRepository $coursRepository): Response
    {
        try {
            $user = $this->getUser();
            
            // Si l'utilisateur est admin, récupérer tous les cours
            if ($this->isGranted('ROLE_ADMIN')) {
                $cours = $coursRepository->findAll();
            } elseif ($this->isGranted('ROLE_ENSEIGNANT')) {
                // Si c'est un enseignant, récupérer tous les cours (car pas de relation createdBy pour l'instant)
                $cours = $coursRepository->findForEnseignant($user);
            } else {
                // Sinon, récupérer uniquement les cours où l'utilisateur a une progression
                $cours = $coursRepository->findByUser($user);
            }
            
            if (empty($cours)) {
                $message = $this->isGranted('ROLE_ADMIN') 
                    ? 'Aucun cours disponible pour le moment.'
                    : ($this->isGranted('ROLE_ENSEIGNANT') 
                        ? 'Aucun cours disponible pour le moment.'
                        : 'Vous n\'êtes inscrit à aucun cours pour le moment.');
                $this->addFlash('info', $message);
            }
            
            return $this->render('cours/index.html.twig', ['cours' => $cours]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du chargement des cours.');
            return $this->render('cours/index.html.twig', ['cours' => []]);
        }
    }

    #[Route('/cours/{id}', name: 'app_cours_show', requirements: ['id' => '\d+'])]
    public function show(Cours $cours, CoursRepository $coursRepository): Response
    {
        // Si l'utilisateur n'est pas admin ou enseignant, vérifier qu'il a accès au cours
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_ENSEIGNANT')) {
            $user = $this->getUser();
            $userCours = $coursRepository->findByUser($user);
            
            $hasAccess = false;
            foreach ($userCours as $userCour) {
                if ($userCour->getId() === $cours->getId()) {
                    $hasAccess = true;
                    break;
                }
            }
            
            if (!$hasAccess) {
                $this->addFlash('error', 'Vous n\'avez pas accès à ce cours.');
                return $this->redirectToRoute('app_cours');
            }
        }
        
        // Si l'utilisateur vient de créer ou modifier un cours en tant qu'enseignant, 
        // il sera redirigé vers la bonne vue ensuite
        return $this->render('cours/show.html.twig', [
            'cours' => $cours,
            'modules' => $cours->getModules(),
        ]);
    }

    #[Route('/cours/new', name: 'app_cours_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        $cours = new Cours();
        $cours->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(CoursType::class, $cours);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Définir l'enseignant créateur du cours
                $cours->setCreatedBy($this->getUser());
                
                $em->persist($cours);
                $em->flush();

                $this->addFlash('success', 'Cours créé avec succès ! Vous pouvez maintenant ajouter des modules.');
                // Rediriger vers la page de gestion des modules du cours
                return $this->redirectToRoute('app_cours_setup', ['id' => $cours->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du cours.');
            }
        }

        return $this->render('cours/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/cours/{id}/edit', name: 'app_cours_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Cours $cours, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        $form = $this->createForm(CoursType::class, $cours);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();

                $this->addFlash('success', 'Cours modifié avec succès !');
                return $this->redirectToRoute('app_cours_show', ['id' => $cours->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification du cours.');
            }
        }

        return $this->render('cours/edit.html.twig', [
            'cours' => $cours,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/cours/{id}/setup', name: 'app_cours_setup', requirements: ['id' => '\d+'])]
    public function setup(Cours $cours): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        // Vérifier que l'utilisateur est le créateur du cours
        if ($cours->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à configurer ce cours.');
        }

        return $this->render('cours/setup.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/cours/{id}/delete', name: 'app_cours_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Cours $cours, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        if ($this->isCsrfTokenValid('delete'.$cours->getId(), $request->request->get('_token'))) {
            try {
                $em->remove($cours);
                $em->flush();

                $this->addFlash('success', 'Cours supprimé avec succès !');
                // Rediriger en fonction du rôle de l'utilisateur
                if ($this->isGranted('ROLE_ENSEIGNANT')) {
                    return $this->redirectToRoute('enseignant_cours');
                } else {
                    return $this->redirectToRoute('app_cours');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression du cours.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_cours_show', ['id' => $cours->getId()]);
    }
}
