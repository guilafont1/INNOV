<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\MessageRepository;

class MessageUnreadService
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
    ) {
    }

    public function countUnread(User $user): int
    {
        return $this->messageRepository->countTotalUnread($user);
    }
}
