<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class SecurityEventListener implements EventSubscriberInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if (!$user instanceof User) {
            return;
        }

        // Mettre à jour la date de dernière connexion
        $user->setDerniereConnexion(new \DateTimeImmutable());
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        if ($token === null) {
            return;
        }

        $user = $token->getUser();
        
        if (!$user instanceof User) {
            return;
        }

        // Mettre à jour la date de dernière déconnexion
        $user->setDerniereDeconnexion(new \DateTimeImmutable());
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
