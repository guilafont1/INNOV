<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Entity\ForumPost;
use App\Entity\User;
use App\Repository\CoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ForumController extends AbstractController
{
    #[Route('/forum/cours/{id}', name: 'app_forum_cours', requirements: ['id' => '\d+'])]
    public function cours(Request $request, int $id, CoursRepository $coursRepository, EntityManagerInterface $em): Response
    {
        $cours = $coursRepository->find($id);
        if (!$cours) {
            $this->addFlash('error', 'Ce cours est introuvable ou a été supprimé.');
            return $this->redirectToRoute('app_cours');
        }

        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        if (!$this->isUserAllowedOnCoursForum($user, $cours)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce forum.');
        }

        if ($request->isMethod('POST')) {
            if (!$this->canPostOnForum($user, $cours)) {
                throw $this->createAccessDeniedException('Vous ne pouvez pas publier sur ce forum.');
            }

            $contenu = trim((string) $request->request->get('contenu'));
            $token = (string) $request->request->get('_token');

            if ($contenu === '') {
                $this->addFlash('error', 'Le contenu du message est requis.');
            } elseif (!$this->isCsrfTokenValid('forum_post_add_' . $cours->getId(), $token)) {
                $this->addFlash('error', 'Token CSRF invalide.');
            } else {
                $post = new ForumPost();
                $post->setAuteur($user);
                $post->setCours($cours);
                $post->setContenu($contenu);
                $post->setCreatedAt(new \DateTimeImmutable());

                $em->persist($post);
                $em->flush();

                $this->addFlash('success', 'Votre message a été publié.');
                return $this->redirectToRoute('app_forum_cours', ['id' => $cours->getId()]);
            }
        }

        $posts = $em->getRepository(ForumPost::class)->findBy(
            ['cours' => $cours],
            ['createdAt' => 'ASC']
        );

        $postsForView = [];
        foreach ($posts as $post) {
            $postsForView[] = [
                'post' => $post,
                'roleLabel' => $this->getRoleLabel($post->getAuteur()),
                'relativeDate' => $post->getCreatedAt() ? $this->formatRelativeDate($post->getCreatedAt()) : '',
            ];
        }

        return $this->render('forum/cours.html.twig', [
            'cours' => $cours,
            'posts' => $postsForView,
            'canPost' => $this->canPostOnForum($user, $cours),
        ]);
    }

    #[Route('/forum/post/{id}/delete', name: 'app_forum_post_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, ForumPost $post, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $canDelete = $post->getAuteur() === $user || $this->isGranted('ROLE_ADMIN_ECOLE');
        if (!$canDelete) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de supprimer ce message.');
        }

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('forum_post_delete_' . $post->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_forum_cours', ['id' => $post->getCours()->getId()]);
        }

        $em->remove($post);
        $em->flush();

        $this->addFlash('success', 'Message supprimé.');

        return $this->redirectToRoute('app_forum_cours', ['id' => $post->getCours()->getId()]);
    }

    private function isUserAllowedOnCoursForum(User $user, Cours $cours): bool
    {
        if ($this->isGranted('ROLE_ADMIN_ECOLE')) {
            return true;
        }

        if ($this->isGranted('ROLE_ENSEIGNANT')) {
            if ($cours->getCreatedBy() !== null && $cours->getCreatedBy() === $user) {
                return true;
            }

            foreach ($cours->getClasses() as $classe) {
                if ($classe->getProfesseurs()->contains($user)) {
                    return true;
                }
            }
        }

        // Étudiants : accès via l'appartenance aux classes
        foreach ($cours->getClasses() as $classe) {
            if ($classe->getEtudiants()->contains($user)) {
                return true;
            }
        }

        return false;
    }

    private function canPostOnForum(User $user, Cours $cours): bool
    {
        if ($this->isGranted('ROLE_ADMIN_ECOLE')) {
            return true;
        }

        if ($this->isGranted('ROLE_ENSEIGNANT')) {
            return false;
        }

        foreach ($cours->getClasses() as $classe) {
            if ($classe->getEtudiants()->contains($user)) {
                return true;
            }
        }

        return false;
    }

    private function getRoleLabel(?User $user): string
    {
        if (!$user) {
            return 'Utilisateur';
        }

        if (in_array('ROLE_ADMIN_ECOLE', $user->getRoles(), true)) {
            return 'Admin';
        }

        if (in_array('ROLE_ENSEIGNANT', $user->getRoles(), true)) {
            return 'Enseignant';
        }

        return 'Étudiant';
    }

    private function formatRelativeDate(\DateTimeImmutable $date): string
    {
        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $date->getTimestamp();
        $abs = abs($diff);

        $minutes = intdiv($abs, 60);
        $hours = intdiv($abs, 3600);
        $days = intdiv($abs, 86400);

        if ($minutes < 1) {
            return 'à l’instant';
        }

        if ($minutes < 60) {
            return 'il y a ' . $minutes . ' minute(s)';
        }

        if ($hours < 24) {
            return 'il y a ' . $hours . ' heure(s)';
        }

        return 'il y a ' . $days . ' jour(s)';
    }
}

