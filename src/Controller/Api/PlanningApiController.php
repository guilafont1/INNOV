<?php

namespace App\Controller\Api;

use App\Entity\Calendrier;
use App\Entity\User;
use App\Repository\CalendrierRepository;
use App\Repository\ClasseRepository;
use App\Repository\CoursRepository;
use App\Repository\UserRepository;
use App\Service\PlanningAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/planning')]
#[IsGranted('ROLE_USER')]
class PlanningApiController extends AbstractController
{
    private const CSRF_TOKEN_ID = 'planning_api';

    public function __construct(
        private CalendrierRepository $calendrierRepository,
        private PlanningAccessService $accessService,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private ClasseRepository $classeRepository,
        private CoursRepository $coursRepository,
    ) {
    }

    #[Route('/events', name: 'api_planning_events', methods: ['GET'])]
    public function listEvents(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $start = new \DateTime($request->query->get('start', 'now'));
            $end = new \DateTime($request->query->get('end', '+1 month'));
        } catch (\Exception) {
            return $this->json(['success' => false, 'message' => 'Période invalide.'], 400);
        }

        $events = $this->calendrierRepository->findForUserScope(
            $user,
            $start,
            $end,
            $this->parseList($request->query->get('type')),
            $this->parseIntList($request->query->get('classe')),
            $this->parseIntList($request->query->get('enseignant')),
            $this->parseIntList($request->query->get('cours')),
        );

        $payload = array_map(
            fn (Calendrier $event) => $this->accessService->serializeEvent($event, $user),
            $events
        );

        return $this->json($payload);
    }

    #[Route('/events', name: 'api_planning_events_create', methods: ['POST'])]
    public function createEvent(Request $request): JsonResponse
    {
        if (!$this->validateCsrf($request)) {
            return $this->json(['success' => false, 'message' => 'Requête non autorisée.'], 403);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->accessService->canCreate($user)) {
            return $this->json(['success' => false, 'message' => 'Action non autorisée.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['success' => false, 'message' => 'Données invalides.'], 400);
        }

        $errors = $this->accessService->validateEventData($data);
        if ($errors !== []) {
            return $this->json(['success' => false, 'message' => 'Validation échouée.', 'errors' => $errors], 422);
        }

        $event = new Calendrier();
        $applyErrors = $this->applyEventData($event, $data, $user);
        if ($applyErrors !== []) {
            return $this->json(['success' => false, 'message' => 'Validation échouée.', 'errors' => $applyErrors], 422);
        }

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Événement créé.',
            'event' => $this->accessService->serializeEvent($event, $user),
        ], 201);
    }

    #[Route('/events/{id}', name: 'api_planning_events_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function updateEvent(int $id, Request $request): JsonResponse
    {
        if (!$this->validateCsrf($request)) {
            return $this->json(['success' => false, 'message' => 'Requête non autorisée.'], 403);
        }

        /** @var User $user */
        $user = $this->getUser();

        $event = $this->calendrierRepository->findOneInUserScope($user, $id);
        if ($event === null) {
            return $this->json(['success' => false, 'message' => 'Événement introuvable.'], 404);
        }

        if (!$this->accessService->canEditEvent($user, $event)) {
            return $this->json(['success' => false, 'message' => 'Action non autorisée.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['success' => false, 'message' => 'Données invalides.'], 400);
        }

        $partial = $this->isPartialUpdate($data);
        $errors = $this->accessService->validateEventData($data, $partial);
        if ($errors !== []) {
            return $this->json(['success' => false, 'message' => 'Validation échouée.', 'errors' => $errors], 422);
        }

        if ($partial) {
            $this->applyPartialDates($event, $data);
        } else {
            $applyErrors = $this->applyEventData($event, $data, $user);
            if ($applyErrors !== []) {
                return $this->json(['success' => false, 'message' => 'Validation échouée.', 'errors' => $applyErrors], 422);
            }
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Événement mis à jour.',
            'event' => $this->accessService->serializeEvent($event, $user),
        ]);
    }

    #[Route('/events/{id}', name: 'api_planning_events_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteEvent(int $id, Request $request): JsonResponse
    {
        if (!$this->validateCsrf($request)) {
            return $this->json(['success' => false, 'message' => 'Requête non autorisée.'], 403);
        }

        /** @var User $user */
        $user = $this->getUser();

        $event = $this->calendrierRepository->findOneInUserScope($user, $id);
        if ($event === null) {
            return $this->json(['success' => false, 'message' => 'Événement introuvable.'], 404);
        }

        if (!$this->accessService->canDeleteEvent($user, $event)) {
            return $this->json(['success' => false, 'message' => 'Action non autorisée.'], 403);
        }

        $this->entityManager->remove($event);
        $this->entityManager->flush();

        return $this->json(['success' => true, 'message' => 'Événement supprimé.']);
    }

    #[Route('/conflicts', name: 'api_planning_conflicts', methods: ['GET'])]
    public function conflicts(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $start = new \DateTime($request->query->get('start'));
            $end = new \DateTime($request->query->get('end'));
        } catch (\Exception) {
            return $this->json(['success' => false, 'message' => 'Période invalide.'], 400);
        }

        $classeId = $request->query->get('classeId') ? (int) $request->query->get('classeId') : null;
        $enseignantId = $request->query->get('enseignantId') ? (int) $request->query->get('enseignantId') : null;
        $excludeId = $request->query->get('excludeId') ? (int) $request->query->get('excludeId') : null;

        $conflicts = $this->calendrierRepository->findConflicts($start, $end, $classeId, $enseignantId, $excludeId);

        // Filtrer selon le scope utilisateur
        $conflicts = array_filter(
            $conflicts,
            fn (Calendrier $event) => $this->userCanSeeEvent($user, $event)
        );

        $payload = array_map(function (Calendrier $event) {
            return [
                'id' => $event->getId(),
                'title' => $event->getTitre(),
                'start' => $event->getDateDebut()?->format('Y-m-d\TH:i:s'),
                'end' => ($event->getDateFin() ?? (clone $event->getDateDebut())->modify('+2 hours'))?->format('Y-m-d\TH:i:s'),
                'classeNom' => $event->getClasse()?->getNom(),
                'enseignantNom' => $this->accessService->formatUserName($event->getEnseignant()),
            ];
        }, array_values($conflicts));

        return $this->json(['success' => true, 'conflicts' => $payload]);
    }

    private function validateCsrf(Request $request): bool
    {
        $token = $request->headers->get('X-CSRF-Token');

        return $this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $token);
    }

    /**
     * @return string[]
     */
    private function parseList(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    /**
     * @return int[]
     */
    private function parseIntList(?string $value): array
    {
        return array_map('intval', $this->parseList($value));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function isPartialUpdate(array $data): bool
    {
        $keys = array_keys($data);

        return $keys !== [] && empty(array_diff($keys, ['start', 'end']));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyPartialDates(Calendrier $event, array $data): void
    {
        if (!empty($data['start'])) {
            $event->setDateDebut(new \DateTime($data['start']));
        }
        if (!empty($data['end'])) {
            $event->setDateFin(new \DateTime($data['end']));
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    private function applyEventData(Calendrier $event, array $data, User $user): array
    {
        $errors = [];

        if (isset($data['titre'])) {
            $event->setTitre(trim((string) $data['titre']));
        }

        if (array_key_exists('description', $data)) {
            $event->setDescription($data['description'] !== '' ? (string) $data['description'] : null);
        }

        if (isset($data['type'])) {
            $event->setType((string) $data['type']);
        }

        $start = $data['start'] ?? $data['date_debut'] ?? null;
        $end = $data['end'] ?? $data['date_fin'] ?? null;

        if ($start) {
            $event->setDateDebut(new \DateTime($start));
        }

        if ($end) {
            $event->setDateFin(new \DateTime($end));
        } elseif ($start) {
            $event->setDateFin((new \DateTime($start))->modify('+2 hours'));
        }

        if (array_key_exists('lieu', $data)) {
            $event->setLieu($data['lieu'] !== '' ? (string) $data['lieu'] : null);
        }

        $role = $this->accessService->getRoleKey($user);

        if (array_key_exists('coursId', $data) || array_key_exists('cours_id', $data)) {
            $coursId = (int) ($data['coursId'] ?? $data['cours_id'] ?? 0);
            if ($coursId > 0) {
                $cours = $this->coursRepository->find($coursId);
                if ($cours && $this->userCanAssignCours($user, $cours)) {
                    $event->setCours($cours);
                } else {
                    $errors['coursId'] = 'Cours non autorisé.';
                }
            } else {
                $event->setCours(null);
            }
        }

        if (array_key_exists('classeId', $data) || array_key_exists('classe_id', $data)) {
            $classeId = (int) ($data['classeId'] ?? $data['classe_id'] ?? 0);
            if ($classeId > 0) {
                $classe = $this->classeRepository->find($classeId);
                if ($classe && $this->userCanAssignClasse($user, $classe)) {
                    $event->setClasse($classe);
                } else {
                    $errors['classeId'] = 'Classe non autorisée.';
                }
            } else {
                $event->setClasse(null);
            }
        }

        if ($role === 'admin' && (array_key_exists('enseignantId', $data) || array_key_exists('enseignant_id', $data))) {
            $enseignantId = (int) ($data['enseignantId'] ?? $data['enseignant_id'] ?? 0);
            if ($enseignantId > 0) {
                $enseignant = $this->userRepository->find($enseignantId);
                if ($enseignant && in_array('ROLE_ENSEIGNANT', $enseignant->getRoles(), true)) {
                    $event->setEnseignant($enseignant);
                } else {
                    $errors['enseignantId'] = 'Enseignant invalide.';
                }
            } else {
                $event->setEnseignant(null);
            }
        } elseif ($role === 'enseignant' && $event->getEnseignant() === null) {
            $event->setEnseignant($user);
        }

        return $errors;
    }

    private function userCanSeeEvent(User $user, Calendrier $event): bool
    {
        $visible = $this->calendrierRepository->findForUserScope(
            $user,
            (clone $event->getDateDebut())->modify('-1 day'),
            (clone ($event->getDateFin() ?? $event->getDateDebut()))->modify('+1 day'),
        );

        foreach ($visible as $item) {
            if ($item->getId() === $event->getId()) {
                return true;
            }
        }

        return false;
    }

    private function userCanAssignCours(User $user, \App\Entity\Cours $cours): bool
    {
        $role = $this->accessService->getRoleKey($user);
        if ($role === 'admin') {
            return true;
        }

        return $cours->getCreatedBy()?->getId() === $user->getId();
    }

    private function userCanAssignClasse(User $user, \App\Entity\Classe $classe): bool
    {
        $role = $this->accessService->getRoleKey($user);
        if ($role === 'admin') {
            return true;
        }

        foreach ($this->classeRepository->findByProfesseur($user) as $item) {
            if ($item->getId() === $classe->getId()) {
                return true;
            }
        }

        return false;
    }
}
