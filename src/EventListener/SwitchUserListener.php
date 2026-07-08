<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\SwitchUserAuthorization;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class SwitchUserListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly SwitchUserAuthorization $switchUserAuthorization,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SwitchUserEvent::class => 'onSwitchUser',
        ];
    }

    public function onSwitchUser(SwitchUserEvent $event): void
    {
        $target = $event->getTargetUser();
        if (!$target instanceof User) {
            return;
        }

        $actor = $this->resolveActor($event->getToken());
        if (!$actor instanceof User) {
            return;
        }

        // Sortie d'impersonation (même utilisateur) — ne pas bloquer
        if ($actor->getId() === $target->getId()) {
            return;
        }

        if (!$this->switchUserAuthorization->canSwitchTo($actor, $target)) {
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à prendre le contrôle de ce compte.');
        }
    }

    private function resolveActor(?TokenInterface $token): ?User
    {
        if ($token === null) {
            return null;
        }

        if ($token instanceof SwitchUserToken) {
            $token = $token->getOriginalToken();
        }

        $user = $token->getUser();

        return $user instanceof User ? $user : null;
    }
}
