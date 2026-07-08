<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\MessageAccessService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MessageController extends AbstractController
{
    #[Route('/messages', name: 'app_messages_inbox', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function inbox(Request $request, MessageAccessService $accessService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $contacts = $accessService->getEligibleContacts($user);

        return $this->render('messages/inbox.html.twig', [
            'contacts' => $contacts,
            'initialPartnerId' => (int) $request->query->get('user', 0),
        ]);
    }
}
