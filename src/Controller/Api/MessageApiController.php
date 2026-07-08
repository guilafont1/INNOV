<?php

namespace App\Controller\Api;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\MessageAccessService;
use App\Service\MessageUnreadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/messages')]
#[IsGranted('ROLE_USER')]
class MessageApiController extends AbstractController
{
    private const CSRF_TOKEN_ID = 'message_api';

    public function __construct(
        private MessageRepository $messageRepository,
        private MessageAccessService $accessService,
        private MessageUnreadService $messageUnreadService,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/conversations', name: 'api_messages_conversations', methods: ['GET'])]
    public function conversations(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $canSwitchScope = $this->accessService->isGlobalViewer($user);
        $scope = $request->query->get('scope', 'personal');

        if ($scope === 'global' && $canSwitchScope) {
            return $this->json([
                'success' => true,
                'scope' => 'global',
                'canSwitchScope' => true,
                'conversations' => $this->buildGlobalViewerConversations($user),
                'totalUnread' => $this->messageUnreadService->countUnread($user),
            ]);
        }

        return $this->json([
            'success' => true,
            'scope' => 'personal',
            'canSwitchScope' => $canSwitchScope,
            'conversations' => $this->buildPersonalConversations($user),
            'totalUnread' => $this->messageUnreadService->countUnread($user),
        ]);
    }

    #[Route('/contacts', name: 'api_messages_contacts', methods: ['GET'])]
    public function contacts(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $contacts = array_map(
            fn (User $contact) => $this->accessService->serializeUser($contact),
            $this->accessService->getEligibleContacts($user),
        );

        return $this->json([
            'success' => true,
            'contacts' => array_values($contacts),
        ]);
    }

    #[Route('/thread/{partnerId}', name: 'api_messages_thread', methods: ['GET'], requirements: ['partnerId' => '\d+'])]
    public function thread(int $partnerId, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $partner = $this->userRepository->find($partnerId);
        $withId = (int) $request->query->get('with', 0);
        $withUser = $withId > 0 ? $this->userRepository->find($withId) : null;

        if (!$partner instanceof User || !$this->accessService->canAccessThread($user, $partner, $withUser)) {
            return $this->json(['success' => false, 'message' => 'Conversation introuvable.'], 404);
        }

        $observerMode = $withUser instanceof User && $withUser->getId() !== $user->getId();

        if ($observerMode) {
            $messages = $this->messageRepository->findThreadMessages($withUser, $partner);

            return $this->json([
                'success' => true,
                'observerMode' => true,
                'participants' => [
                    $this->accessService->serializeUser($withUser),
                    $this->accessService->serializeUser($partner),
                ],
                'partner' => $this->accessService->serializeUser($partner),
                'displayName' => $this->accessService->getConversationDisplayName($withUser, $partner),
                'messages' => array_map(fn (Message $message) => $this->serializeMessage($message, $user), $messages),
                'totalUnread' => $this->messageUnreadService->countUnread($user),
            ]);
        }

        if (!$this->accessService->canMessage($user, $partner) && !$this->accessService->isGlobalViewer($user)) {
            return $this->json(['success' => false, 'message' => 'Conversation introuvable.'], 404);
        }

        $this->messageRepository->markThreadAsRead($user, $partner);
        $messages = $this->messageRepository->findThreadMessages($user, $partner);

        return $this->json([
            'success' => true,
            'observerMode' => false,
            'partner' => $this->accessService->serializeUser($partner),
            'messages' => array_map(fn (Message $message) => $this->serializeMessage($message, $user), $messages),
            'totalUnread' => $this->messageUnreadService->countUnread($user),
        ]);
    }

    #[Route('/send', name: 'api_messages_send', methods: ['POST'])]
    public function send(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['success' => false, 'message' => 'Corps de requête invalide.'], 400);
        }

        $partnerId = (int) ($payload['partnerId'] ?? 0);
        $contenu = trim((string) ($payload['contenu'] ?? ''));
        $token = (string) ($payload['_token'] ?? '');

        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF invalide.'], 403);
        }

        $partner = $this->userRepository->find($partnerId);
        if (!$partner instanceof User || !$this->accessService->canMessage($user, $partner)) {
            return $this->json(['success' => false, 'message' => 'Destinataire invalide.'], 400);
        }

        if ($contenu === '') {
            return $this->json(['success' => false, 'message' => 'Le message ne peut pas être vide.'], 400);
        }

        if (mb_strlen($contenu) > 5000) {
            return $this->json(['success' => false, 'message' => 'Message trop long (5000 caractères max).'], 400);
        }

        $message = new Message();
        $message->setExpediteur($user);
        $message->setDestinataire($partner);
        $message->setContenu($contenu);
        $message->setSentAt(new \DateTimeImmutable());

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => $this->serializeMessage($message, $user),
        ], 201);
    }

    #[Route('/thread/{partnerId}/read', name: 'api_messages_read', methods: ['POST'], requirements: ['partnerId' => '\d+'])]
    public function markRead(int $partnerId, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $token = (string) $request->headers->get('X-CSRF-TOKEN', '');

        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF invalide.'], 403);
        }

        $partner = $this->userRepository->find($partnerId);
        if (!$partner instanceof User || !$this->accessService->canAccessThread($user, $partner)) {
            return $this->json(['success' => false, 'message' => 'Conversation introuvable.'], 404);
        }

        $this->messageRepository->markThreadAsRead($user, $partner);

        return $this->json([
            'success' => true,
            'totalUnread' => $this->messageUnreadService->countUnread($user),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeConversation(User $user, User $partner, Message $lastMessage, int $unreadCount): array
    {
        $isMine = $lastMessage->getExpediteur()?->getId() === $user->getId();
        $preview = $lastMessage->getContenu() ?? '';
        if (mb_strlen($preview) > 80) {
            $preview = mb_substr($preview, 0, 80) . '…';
        }

        return [
            'partner' => $this->accessService->serializeUser($partner),
            'lastMessage' => $this->serializeMessage($lastMessage, $user),
            'preview' => $preview,
            'sentAt' => $lastMessage->getSentAt()?->format(\DateTimeInterface::ATOM),
            'unreadCount' => $unreadCount,
            'isMine' => $isMine,
            'observerMode' => false,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildPersonalConversations(User $user): array
    {
        $summaries = $this->messageRepository->findConversationSummaries($user);
        $contacts = $this->accessService->getEligibleContacts($user);

        $items = [];
        foreach ($summaries as $summary) {
            $partner = $summary['partner'];
            $items[] = $this->serializeConversation(
                $user,
                $partner,
                $summary['lastMessage'],
                $summary['unreadCount'],
            );
            unset($contacts[$partner->getId()]);
        }

        foreach ($contacts as $contact) {
            $items[] = [
                'partner' => $this->accessService->serializeUser($contact),
                'lastMessage' => null,
                'preview' => 'Démarrer une conversation',
                'sentAt' => null,
                'unreadCount' => 0,
                'isMine' => false,
                'observerMode' => false,
            ];
        }

        usort($items, $this->sortConversations(...));

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildGlobalViewerConversations(User $user): array
    {
        $items = [];

        foreach ($this->messageRepository->findAllConversationPairs() as $pair) {
            $userA = $pair['userA'];
            $userB = $pair['userB'];
            $lastMessage = $pair['lastMessage'];

            $involvesViewer = $userA->getId() === $user->getId() || $userB->getId() === $user->getId();

            if ($involvesViewer) {
                $partner = $userA->getId() === $user->getId() ? $userB : $userA;
                $items[] = $this->serializeConversation(
                    $user,
                    $partner,
                    $lastMessage,
                    $this->messageRepository->countUnreadFrom($user, $partner),
                );
                continue;
            }

            $preview = $lastMessage->getContenu() ?? '';
            if (mb_strlen($preview) > 80) {
                $preview = mb_substr($preview, 0, 80) . '…';
            }

            $expediteur = $lastMessage->getExpediteur();
            $senderName = $expediteur ? $this->accessService->getUserDisplayName($expediteur) : 'Utilisateur';

            $items[] = [
                'observerMode' => true,
                'partner' => $this->accessService->serializeUser($userB),
                'participants' => [
                    $this->accessService->serializeUser($userA),
                    $this->accessService->serializeUser($userB),
                ],
                'participantIds' => [$userA->getId(), $userB->getId()],
                'displayName' => $this->accessService->getConversationDisplayName($userA, $userB),
                'lastMessage' => $this->serializeMessage($lastMessage, $user),
                'preview' => $senderName . ' : ' . $preview,
                'sentAt' => $lastMessage->getSentAt()?->format(\DateTimeInterface::ATOM),
                'unreadCount' => 0,
                'isMine' => false,
            ];
        }

        usort($items, $this->sortConversations(...));

        return $items;
    }

    /**
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     */
    private function sortConversations(array $a, array $b): int
    {
        $timeA = $a['sentAt'] ?? '';
        $timeB = $b['sentAt'] ?? '';
        if ($timeA === $timeB) {
            $nameA = $a['displayName'] ?? $a['partner']['name'] ?? '';
            $nameB = $b['displayName'] ?? $b['partner']['name'] ?? '';

            return strcasecmp($nameA, $nameB);
        }
        if ($timeA === '') {
            return 1;
        }
        if ($timeB === '') {
            return -1;
        }

        return strcmp($timeB, $timeA);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMessage(Message $message, User $currentUser): array
    {
        $isMine = $message->getExpediteur()?->getId() === $currentUser->getId();

        return [
            'id' => $message->getId(),
            'contenu' => $message->getContenu(),
            'sentAt' => $message->getSentAt()?->format(\DateTimeInterface::ATOM),
            'isMine' => $isMine,
            'isRead' => $message->isRead(),
            'expediteur' => $message->getExpediteur()
                ? $this->accessService->serializeUser($message->getExpediteur())
                : null,
        ];
    }
}
