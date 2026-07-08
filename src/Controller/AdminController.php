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
use App\Service\AdminDashboardAnalyticsService;
use App\Service\PlanningPageService;
use App\Security\UserRole;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin')]
#[IsGranted(UserRole::ADMIN_ECOLE)]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(
        ClasseRepository $classeRepository,
        UserRepository $userRepository,
        CoursRepository $coursRepository,
        AdminDashboardAnalyticsService $dashboardAnalytics,
    ): Response {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $isSuperAdmin = $currentUser instanceof User && UserRole::isSuperAdmin($currentUser->getRoles());

        if ($isSuperAdmin) {
            $analytics = $dashboardAnalytics->buildSuperAdmin();

            return $this->render('admin/dashboard.html.twig', [
                'dashboardMode' => 'super_admin',
                'isSuperAdmin' => true,
                'stats' => $analytics['stats'],
                'charts' => $analytics['charts'],
                'recent_classes' => $classeRepository->findBy([], ['createdAt' => 'DESC'], 5),
                'recent_users' => $userRepository->findBy([], ['id' => 'DESC'], 5),
            ]);
        }

        $analytics = $dashboardAnalytics->buildSchoolAdmin();

        return $this->render('admin/dashboard.html.twig', [
            'dashboardMode' => 'school_admin',
            'isSuperAdmin' => false,
            'stats' => $analytics['stats'],
            'charts' => $analytics['charts'],
            'class_summaries' => $analytics['classSummaries'],
            'top_students' => $analytics['topStudents'],
            'students_to_watch' => $analytics['studentsToWatch'],
            'recent_classes' => $classeRepository->findBy([], ['createdAt' => 'DESC'], 5),
            'recent_users' => $userRepository->findBy([], ['id' => 'DESC'], 5),
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

            $this->addFlash('success', 'Classe créée.');
            return $this->redirectToRoute('admin_classes');
        }

        return $this->render('admin/classes/new.html.twig', [
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

            $this->addFlash('success', 'Classe mise à jour.');
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
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('admin_classes_delete_'.$classe->getId(), $token)) {
            $this->addFlash('error', 'Requête invalide.');
            return $this->redirectToRoute('admin_classes');
        }

        $entityManager->remove($classe);
        $entityManager->flush();

        $this->addFlash('success', 'Classe supprimée.');
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
        $token = $request->request->get('_token');
        $student = $userRepository->find($studentId);

        if (!$this->isCsrfTokenValid('admin_classes_add_student_'.$classe->getId().'_'.$studentId, $token)) {
            $this->addFlash('error', 'Requête invalide.');
            return $this->redirectToRoute('admin_classes_manage', ['id' => $classe->getId()]);
        }

        if ($student && in_array('ROLE_ETUDIANT', $student->getRoles())) {
            $classe->addEtudiant($student);
            $entityManager->flush();

            $this->addFlash('success', 'Étudiant ajouté à la classe.');
        } else {
            $this->addFlash('error', 'Étudiant non trouvé ou invalide.');
        }

        return $this->redirectToRoute('admin_classes_manage', ['id' => $classe->getId()]);
    }

    #[Route('/classes/{id}/remove-student/{studentId}', name: 'admin_classes_remove_student', methods: ['POST'])]
    public function removeStudentFromClasse(
        Classe $classe,
        int $studentId,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('admin_classes_remove_student_'.$classe->getId().'_'.$studentId, $token)) {
            $this->addFlash('error', 'Requête invalide.');
            return $this->redirectToRoute('admin_classes_manage', ['id' => $classe->getId()]);
        }

        $student = $userRepository->find($studentId);

        if ($student) {
            $classe->removeEtudiant($student);
            $entityManager->flush();

            $this->addFlash('success', 'Étudiant retiré de la classe.');
        } else {
            $this->addFlash('error', 'Étudiant non trouvé.');
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
        $token = $request->request->get('_token');
        $teacher = $userRepository->find($teacherId);

        if (!$this->isCsrfTokenValid('admin_classes_add_teacher_'.$classe->getId().'_'.$teacherId, $token)) {
            $this->addFlash('error', 'Requête invalide.');
            return $this->redirectToRoute('admin_classes_manage', ['id' => $classe->getId()]);
        }

        if ($teacher && in_array('ROLE_ENSEIGNANT', $teacher->getRoles())) {
            $classe->addProfesseur($teacher);
            $entityManager->flush();

            $this->addFlash('success', 'Professeur ajouté à la classe.');
        } else {
            $this->addFlash('error', 'Professeur non trouvé ou invalide.');
        }

        return $this->redirectToRoute('admin_classes_manage', ['id' => $classe->getId()]);
    }

    #[Route('/classes/{id}/remove-teacher/{teacherId}', name: 'admin_classes_remove_teacher', methods: ['POST'])]
    public function removeTeacherFromClasse(
        Classe $classe,
        int $teacherId,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('admin_classes_remove_teacher_'.$classe->getId().'_'.$teacherId, $token)) {
            $this->addFlash('error', 'Requête invalide.');
            return $this->redirectToRoute('admin_classes_manage', ['id' => $classe->getId()]);
        }

        $teacher = $userRepository->find($teacherId);

        if ($teacher) {
            $classe->removeProfesseur($teacher);
            $entityManager->flush();

            $this->addFlash('success', 'Professeur retiré de la classe.');
        } else {
            $this->addFlash('error', 'Professeur non trouvé.');
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
        $token = $request->request->get('_token');
        $course = $coursRepository->find($courseId);

        if (!$this->isCsrfTokenValid('admin_classes_add_course_'.$classe->getId().'_'.$courseId, $token)) {
            $this->addFlash('error', 'Requête invalide.');
            return $this->redirectToRoute('admin_classes_manage', ['id' => $classe->getId()]);
        }

        if ($course) {
            $classe->addCours($course);
            $entityManager->flush();

            $this->addFlash('success', 'Cours ajouté à la classe.');
        } else {
            $this->addFlash('error', 'Cours non trouvé.');
        }

        return $this->redirectToRoute('admin_classes_manage', ['id' => $classe->getId()]);
    }

    #[Route('/classes/{id}/remove-course/{courseId}', name: 'admin_classes_remove_course', methods: ['POST'])]
    public function removeCourseFromClasse(
        Classe $classe,
        int $courseId,
        Request $request,
        CoursRepository $coursRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('admin_classes_remove_course_'.$classe->getId().'_'.$courseId, $token)) {
            $this->addFlash('error', 'Requête invalide.');
            return $this->redirectToRoute('admin_classes_manage', ['id' => $classe->getId()]);
        }

        $course = $coursRepository->find($courseId);

        if ($course) {
            $classe->removeCours($course);
            $entityManager->flush();

            $this->addFlash('success', 'Cours retiré de la classe.');
        } else {
            $this->addFlash('error', 'Cours non trouvé.');
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
        $form = $this->createForm(AdminUserType::class, $user, [
            'allow_super_admin' => $this->isGranted(UserRole::SUPER_ADMIN),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $roleChoice = $form->get('roleChoice')->getData();

            if (!$this->canAssignRole($roleChoice)) {
                $this->addFlash('error', 'Vous n\'êtes pas autorisé à attribuer ce rôle.');
                return $this->redirectToRoute('admin_users');
            }

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setRoles([$roleChoice]);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé.');
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
        $this->denyIfCannotModifyUser($user);

        $token = $request->request->get('_token');
        $newRole = $request->request->get('role');

        if (!$this->isCsrfTokenValid('admin_users_toggle_role_'.$user->getId(), $token)) {
            $this->addFlash('error', 'Requête invalide.');
            return $this->redirectToRoute('admin_users');
        }

        if (!$this->canAssignRole($newRole)) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à attribuer ce rôle.');
            return $this->redirectToRoute('admin_users');
        }

        if (in_array($newRole, UserRole::ASSIGNABLE, true)) {
            $user->setRoles([$newRole]);
            $entityManager->flush();

            $this->addFlash('success', 'Rôle mis à jour.');
        } else {
            $this->addFlash('error', 'Rôle invalide.');
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/delete', name: 'admin_users_delete', methods: ['POST'])]
    public function deleteUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyIfCannotModifyUser($user);

        $token = $request->request->get('_token');
        // Empêcher la suppression de son propre compte
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_users');
        }

        if (!$this->isCsrfTokenValid('admin_users_delete_'.$user->getId(), $token)) {
            $this->addFlash('error', 'Requête invalide.');
            return $this->redirectToRoute('admin_users');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur supprimé.');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/planning', name: 'admin_planning')]
    public function planning(PlanningPageService $planningPageService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('planning/index.html.twig', $planningPageService->buildPageContext($user));
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
            'can_manage_super_admin' => $this->isGranted(UserRole::SUPER_ADMIN),
        ]);
    }

    private function canAssignRole(?string $role): bool
    {
        if ($role === null || !in_array($role, UserRole::ASSIGNABLE, true)) {
            return false;
        }

        if ($role === UserRole::SUPER_ADMIN) {
            return $this->isGranted(UserRole::SUPER_ADMIN);
        }

        return $this->isGranted(UserRole::ADMIN_ECOLE);
    }

    private function denyIfCannotModifyUser(User $target): void
    {
        if (UserRole::isSuperAdmin($target->getRoles()) && !$this->isGranted(UserRole::SUPER_ADMIN)) {
            throw $this->createAccessDeniedException('Seul un super administrateur peut modifier ce compte.');
        }
    }
}
