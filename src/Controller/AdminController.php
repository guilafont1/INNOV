<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\User;
use App\Entity\Cours;
use App\Entity\Calendrier;
use App\Form\ClasseType;
use App\Form\AdminUserType;
use App\Repository\ClasseRepository;
use App\Repository\UserRepository;
use App\Repository\CoursRepository;
use App\Repository\CalendrierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(
        ClasseRepository $classeRepository,
        UserRepository $userRepository,
        CoursRepository $coursRepository
    ): Response {
        $stats = [
            'totalClasses' => $classeRepository->count([]),
            'totalEtudiants' => count($userRepository->findByRole('ROLE_ETUDIANT')),
            'totalEnseignants' => count($userRepository->findByRole('ROLE_ENSEIGNANT')),
            'totalCours' => $coursRepository->count([]),
        ];

        $recentClasses = $classeRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $recentUsers = $userRepository->findBy([], ['id' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'recent_classes' => $recentClasses,
            'recent_users' => $recentUsers,
        ]);
    }

    #[Route('/classes', name: 'admin_classes')]
    public function listClasses(ClasseRepository $classeRepository): Response
    {
        $classes = $classeRepository->findAll();

        return $this->render('admin/classes/index.html.twig', [
            'classes' => $classes,
        ]);
    }

    #[Route('/classes/new', name: 'admin_classes_new')]
    public function newClasse(Request $request, EntityManagerInterface $entityManager): Response
    {
        $classe = new Classe();
        $form = $this->createForm(ClasseType::class, $classe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $classe->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($classe);
            $entityManager->flush();

            $this->addFlash('success', 'Classe créée avec succès !');
            return $this->redirectToRoute('admin_classes');
        }

        return $this->render('admin/classes/new_simple.html.twig', [
            'classe' => $classe,
            'form' => $form,
        ]);
    }

    #[Route('/classes/{id}/edit', name: 'admin_classes_edit')]
    public function editClasse(
        Classe $classe,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(ClasseType::class, $classe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $classe->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Classe mise à jour avec succès !');
            return $this->redirectToRoute('admin_classes');
        }

        return $this->render('admin/classes/edit.html.twig', [
            'classe' => $classe,
            'form' => $form,
        ]);
    }

    #[Route('/classes/{id}/delete', name: 'admin_classes_delete', methods: ['POST'])]
    public function deleteClasse(
        Classe $classe,
        EntityManagerInterface $entityManager
    ): Response {
        $entityManager->remove($classe);
        $entityManager->flush();

        $this->addFlash('success', 'Classe supprimée avec succès !');
        return $this->redirectToRoute('admin_classes');
    }

    #[Route('/classes/{id}/manage', name: 'admin_classes_manage')]
    public function manageClasse(
        Classe $classe,
        UserRepository $userRepository,
        CoursRepository $coursRepository
    ): Response {
        $etudiants = $userRepository->findByRole('ROLE_ETUDIANT');
        $enseignants = $userRepository->findByRole('ROLE_ENSEIGNANT');
        $cours = $coursRepository->findAll();

        return $this->render('admin/classes/manage.html.twig', [
            'classe' => $classe,
            'available_etudiants' => $etudiants,
            'available_enseignants' => $enseignants,
            'available_cours' => $cours,
        ]);
    }

    #[Route('/classes/{id}/add-student', name: 'admin_classes_add_student', methods: ['POST'])]
    public function addStudentToClasse(
        Classe $classe,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $studentId = $request->request->get('student_id');
        $student = $userRepository->find($studentId);

        if ($student && in_array('ROLE_ETUDIANT', $student->getRoles())) {
            $classe->addEtudiant($student);
            $entityManager->flush();

            $this->addFlash('success', 'Étudiant ajouté à la classe avec succès !');
        } else {
            $this->addFlash('error', 'Étudiant non trouvé ou invalide !');
        }

        return $this->redirectToRoute('admin_classes_manage', ['id' => $classe->getId()]);
    }

    #[Route('/classes/{id}/remove-student/{studentId}', name: 'admin_classes_remove_student', methods: ['POST'])]
    public function removeStudentFromClasse(
        Classe $classe,
        int $studentId,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $student = $userRepository->find($studentId);

        if ($student) {
            $classe->removeEtudiant($student);
            $entityManager->flush();

            $this->addFlash('success', 'Étudiant retiré de la classe avec succès !');
        } else {
            $this->addFlash('error', 'Étudiant non trouvé !');
        }

        return $this->redirectToRoute('admin_classes_manage', ['id' => $classe->getId()]);
    }

    #[Route('/classes/{id}/add-teacher', name: 'admin_classes_add_teacher', methods: ['POST'])]
    public function addTeacherToClasse(
        Classe $classe,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $teacherId = $request->request->get('teacher_id');
        $teacher = $userRepository->find($teacherId);

        if ($teacher && in_array('ROLE_ENSEIGNANT', $teacher->getRoles())) {
            $classe->addProfesseur($teacher);
            $entityManager->flush();

            $this->addFlash('success', 'Professeur ajouté à la classe avec succès !');
        } else {
            $this->addFlash('error', 'Professeur non trouvé ou invalide !');
        }

        return $this->redirectToRoute('admin_classes_manage', ['id' => $classe->getId()]);
    }

    #[Route('/classes/{id}/remove-teacher/{teacherId}', name: 'admin_classes_remove_teacher', methods: ['POST'])]
    public function removeTeacherFromClasse(
        Classe $classe,
        int $teacherId,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $teacher = $userRepository->find($teacherId);

        if ($teacher) {
            $classe->removeProfesseur($teacher);
            $entityManager->flush();

            $this->addFlash('success', 'Professeur retiré de la classe avec succès !');
        } else {
            $this->addFlash('error', 'Professeur non trouvé !');
        }

        return $this->redirectToRoute('admin_classes_manage', ['id' => $classe->getId()]);
    }

    #[Route('/classes/{id}/add-course', name: 'admin_classes_add_course', methods: ['POST'])]
    public function addCourseToClasse(
        Classe $classe,
        Request $request,
        CoursRepository $coursRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $courseId = $request->request->get('course_id');
        $course = $coursRepository->find($courseId);

        if ($course) {
            $classe->addCours($course);
            $entityManager->flush();

            $this->addFlash('success', 'Cours ajouté à la classe avec succès !');
        } else {
            $this->addFlash('error', 'Cours non trouvé !');
        }

        return $this->redirectToRoute('admin_classes_manage', ['id' => $classe->getId()]);
    }

    #[Route('/classes/{id}/remove-course/{courseId}', name: 'admin_classes_remove_course', methods: ['POST'])]
    public function removeCourseFromClasse(
        Classe $classe,
        int $courseId,
        CoursRepository $coursRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $course = $coursRepository->find($courseId);

        if ($course) {
            $classe->removeCours($course);
            $entityManager->flush();

            $this->addFlash('success', 'Cours retiré de la classe avec succès !');
        } else {
            $this->addFlash('error', 'Cours non trouvé !');
        }

        return $this->redirectToRoute('admin_classes_manage', ['id' => $classe->getId()]);
    }

    #[Route('/users', name: 'admin_users')]
    public function listUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/new', name: 'admin_users_new')]
    public function newUser(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $roleChoice = $form->get('roleChoice')->getData();

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setRoles([$roleChoice]);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès !');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/users/{id}/toggle-role', name: 'admin_users_toggle_role', methods: ['POST'])]
    public function toggleUserRole(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $newRole = $request->request->get('role');
        $validRoles = ['ROLE_ETUDIANT', 'ROLE_ENSEIGNANT', 'ROLE_ADMIN'];

        if (in_array($newRole, $validRoles)) {
            $user->setRoles([$newRole]);
            $entityManager->flush();

            $this->addFlash('success', 'Rôle de l\'utilisateur mis à jour avec succès !');
        } else {
            $this->addFlash('error', 'Rôle invalide !');
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/delete', name: 'admin_users_delete', methods: ['POST'])]
    public function deleteUser(
        User $user,
        EntityManagerInterface $entityManager
    ): Response {
        // Empêcher la suppression de son propre compte
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte !');
            return $this->redirectToRoute('admin_users');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès !');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/planning', name: 'admin_planning')]
    public function planning(CalendrierRepository $calendrierRepository): Response
    {
        $events = $calendrierRepository->findAll();

        return $this->render('admin/planning/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/classes/{id}', name: 'admin_classes_show')]
    public function showClasse(Classe $classe): Response
    {
        return $this->render('admin/classes/show.html.twig', [
            'classe' => $classe,
        ]);
    }

    #[Route('/users/{id}', name: 'admin_users_show')]
    public function showUser(User $user): Response
    {
        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
        ]);
    }
}
