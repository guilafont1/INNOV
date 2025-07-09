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
    public function planning(
        Request $request,
        CalendrierRepository $calendrierRepository,
        UserRepository $userRepository,
        ClasseRepository $classeRepository,
        CoursRepository $coursRepository
    ): Response
    {
        // Récupérer le filtre de date
        $currentDate = new \DateTime();
        $startOfWeek = clone $currentDate;
        $startOfWeek->modify('this week monday');
        
        $endOfWeek = clone $startOfWeek;
        $endOfWeek->modify('+6 days');
        
        $dateStart = $request->query->get('date_start') 
            ? new \DateTime($request->query->get('date_start')) 
            : $startOfWeek;
            
        $dateEnd = $request->query->get('date_end') 
            ? new \DateTime($request->query->get('date_end')) 
            : $endOfWeek;
            
        $type = $request->query->get('type');
        $classeId = $request->query->get('classe') ? (int) $request->query->get('classe') : null;
        $enseignantId = $request->query->get('enseignant') ? (int) $request->query->get('enseignant') : null;
        
        // Récupérer les événements filtrés
        $events = $calendrierRepository->findByDateRange($dateStart, $dateEnd, $type, $classeId, $enseignantId);
        
        // Organiser les événements par jour et heure pour l'affichage en grille
        $weekDays = [];
        $currentDay = clone $dateStart;
        
        // Définir les horaires des créneaux (de 8h à 18h)
        $timeSlots = [];
        for ($hour = 8; $hour <= 18; $hour++) {
            $timeSlots[] = sprintf('%02d:00', $hour);
            if ($hour < 18) {
                $timeSlots[] = sprintf('%02d:30', $hour);
            }
        }
        
        // Créer la structure de la semaine
        while ($currentDay <= $dateEnd) {
            $dayKey = $currentDay->format('Y-m-d');
            $weekDays[$dayKey] = [
                'date' => clone $currentDay,
                'events' => [],
            ];
            $currentDay->modify('+1 day');
        }
        
        // Répartir les événements dans la structure
        foreach ($events as $event) {
            $eventDay = $event->getDateDebut()->format('Y-m-d');
            if (isset($weekDays[$eventDay])) {
                $weekDays[$eventDay]['events'][] = $event;
            }
        }
        
        // Récupérer toutes les classes et enseignants pour les filtres et le formulaire
        $classes = $classeRepository->findAll();
        $enseignants = $userRepository->findByRole('ROLE_ENSEIGNANT');
        $cours = $coursRepository->findAll();
        
        return $this->render('admin/planning/index.html.twig', [
            'weekDays' => $weekDays,
            'timeSlots' => $timeSlots,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
            'classes' => $classes,
            'enseignants' => $enseignants,
            'cours' => $cours,
            'currentDate' => $currentDate,
        ]);
    }
    
    #[Route('/planning/event/new', name: 'admin_planning_new_event', methods: ['POST'])]
    public function newEvent(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ClasseRepository $classeRepository,
        CoursRepository $coursRepository
    ): Response
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['success' => false, 'message' => 'Données invalides'], 400);
        }
        
        $event = new Calendrier();
        $event->setTitre($data['titre']);
        $event->setDescription($data['description'] ?? null);
        $event->setType($data['type'] ?? 'cours');
        
        // Date de début
        $dateDebut = new \DateTime($data['date_debut']);
        $event->setDateDebut($dateDebut);
        
        // Date de fin (optionnelle)
        if (!empty($data['date_fin'])) {
            $dateFin = new \DateTime($data['date_fin']);
            $event->setDateFin($dateFin);
        }
        
        // Lieu
        if (!empty($data['lieu'])) {
            $event->setLieu($data['lieu']);
        }
        
        // Relations
        if (!empty($data['enseignant_id'])) {
            $enseignant = $userRepository->find($data['enseignant_id']);
            if ($enseignant) {
                $event->setEnseignant($enseignant);
            }
        }
        
        if (!empty($data['classe_id'])) {
            $classe = $classeRepository->find($data['classe_id']);
            if ($classe) {
                $event->setClasse($classe);
            }
        }
        
        if (!empty($data['cours_id'])) {
            $cours = $coursRepository->find($data['cours_id']);
            if ($cours) {
                $event->setCours($cours);
            }
        }
        
        $entityManager->persist($event);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Événement créé avec succès',
            'event' => [
                'id' => $event->getId(),
                'titre' => $event->getTitre(),
                'date' => $event->getDateDebut()->format('d/m/Y H:i')
            ]
        ]);
    }
    
    #[Route('/planning/event/{id}/edit', name: 'admin_planning_edit_event', methods: ['POST'])]
    public function editEvent(
        Calendrier $event,
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ClasseRepository $classeRepository,
        CoursRepository $coursRepository
    ): Response
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['success' => false, 'message' => 'Données invalides'], 400);
        }
        
        $event->setTitre($data['titre']);
        $event->setDescription($data['description'] ?? null);
        $event->setType($data['type'] ?? 'cours');
        
        // Date de début
        $dateDebut = new \DateTime($data['date_debut']);
        $event->setDateDebut($dateDebut);
        
        // Date de fin (optionnelle)
        if (!empty($data['date_fin'])) {
            $dateFin = new \DateTime($data['date_fin']);
            $event->setDateFin($dateFin);
        } else {
            $event->setDateFin(null);
        }
        
        // Lieu
        $event->setLieu($data['lieu'] ?? null);
        
        // Relations
        if (!empty($data['enseignant_id'])) {
            $enseignant = $userRepository->find($data['enseignant_id']);
            if ($enseignant) {
                $event->setEnseignant($enseignant);
            }
        } else {
            $event->setEnseignant(null);
        }
        
        if (!empty($data['classe_id'])) {
            $classe = $classeRepository->find($data['classe_id']);
            if ($classe) {
                $event->setClasse($classe);
            }
        } else {
            $event->setClasse(null);
        }
        
        if (!empty($data['cours_id'])) {
            $cours = $coursRepository->find($data['cours_id']);
            if ($cours) {
                $event->setCours($cours);
            }
        } else {
            $event->setCours(null);
        }
        
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Événement modifié avec succès',
            'event' => [
                'id' => $event->getId(),
                'titre' => $event->getTitre(),
                'date' => $event->getDateDebut()->format('d/m/Y H:i')
            ]
        ]);
    }
    
    #[Route('/planning/event/{id}/delete', name: 'admin_planning_delete_event', methods: ['POST'])]
    public function deleteEvent(
        Calendrier $event,
        EntityManagerInterface $entityManager
    ): Response
    {
        $entityManager->remove($event);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Événement supprimé avec succès'
        ]);
    }
    
    #[Route('/planning/event/{id}/get-details', name: 'admin_planning_get_event_details', methods: ['GET'])]
    public function getEventDetails(Calendrier $event): Response
    {
        $eventData = [
            'id' => $event->getId(),
            'titre' => $event->getTitre(),
            'description' => $event->getDescription(),
            'type' => $event->getType(),
            'lieu' => $event->getLieu(),
            'date_debut' => $event->getDateDebut()->format('Y-m-d H:i:s'),
            'enseignant_id' => $event->getEnseignant() ? $event->getEnseignant()->getId() : null,
            'classe_id' => $event->getClasse() ? $event->getClasse()->getId() : null,
            'cours_id' => $event->getCours() ? $event->getCours()->getId() : null,
        ];
        
        if ($event->getDateFin()) {
            $eventData['date_fin'] = $event->getDateFin()->format('Y-m-d H:i:s');
        }
        
        return $this->json([
            'success' => true,
            'event' => $eventData
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
