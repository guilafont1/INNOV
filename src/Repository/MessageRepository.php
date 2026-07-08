<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * @return Message[]
     */
    public function findThreadMessages(User $user, User $partner): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.expediteur', 'e')->addSelect('e')
            ->leftJoin('m.destinataire', 'd')->addSelect('d')
            ->where('(m.expediteur = :user AND m.destinataire = :partner) OR (m.expediteur = :partner AND m.destinataire = :user)')
            ->setParameter('user', $user)
            ->setParameter('partner', $partner)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{partner: User, lastMessage: Message, unreadCount: int}>
     */
    public function findConversationSummaries(User $user): array
    {
        $messages = $this->createQueryBuilder('m')
            ->leftJoin('m.expediteur', 'e')->addSelect('e')
            ->leftJoin('m.destinataire', 'd')->addSelect('d')
            ->where('m.expediteur = :user OR m.destinataire = :user')
            ->setParameter('user', $user)
            ->orderBy('m.sentAt', 'DESC')
            ->getQuery()
            ->getResult();

        $userId = $user->getId();
        $summaries = [];

        foreach ($messages as $message) {
            $partner = $message->getExpediteur()?->getId() === $userId
                ? $message->getDestinataire()
                : $message->getExpediteur();

            if ($partner === null) {
                continue;
            }

            $partnerId = $partner->getId();
            if (!isset($summaries[$partnerId])) {
                $summaries[$partnerId] = [
                    'partner' => $partner,
                    'lastMessage' => $message,
                    'unreadCount' => $this->countUnreadFrom($user, $partner),
                ];
            }
        }

        return $summaries;
    }

    public function countUnreadFrom(User $reader, User $sender): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.expediteur = :sender')
            ->andWhere('m.destinataire = :reader')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('sender', $sender)
            ->setParameter('reader', $reader)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countTotalUnread(User $user): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.destinataire = :user')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAllUnread(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.readAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markThreadAsRead(User $reader, User $partner): int
    {
        return $this->createQueryBuilder('m')
            ->update()
            ->set('m.readAt', ':now')
            ->where('m.expediteur = :partner')
            ->andWhere('m.destinataire = :reader')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('partner', $partner)
            ->setParameter('reader', $reader)
            ->getQuery()
            ->execute();
    }

    /**
     * @return array<int, array{userA: User, userB: User, lastMessage: Message}>
     */
    public function findAllConversationPairs(): array
    {
        $messages = $this->createQueryBuilder('m')
            ->leftJoin('m.expediteur', 'e')->addSelect('e')
            ->leftJoin('m.destinataire', 'd')->addSelect('d')
            ->orderBy('m.sentAt', 'DESC')
            ->getQuery()
            ->getResult();

        $pairs = [];

        foreach ($messages as $message) {
            $expediteur = $message->getExpediteur();
            $destinataire = $message->getDestinataire();

            if ($expediteur === null || $destinataire === null) {
                continue;
            }

            $first = $expediteur->getId() < $destinataire->getId() ? $expediteur : $destinataire;
            $second = $expediteur->getId() < $destinataire->getId() ? $destinataire : $expediteur;
            $key = $first->getId() . '-' . $second->getId();

            if (!isset($pairs[$key])) {
                $pairs[$key] = [
                    'userA' => $first,
                    'userB' => $second,
                    'lastMessage' => $message,
                ];
            }
        }

        return $pairs;
    }

    /**
     * @return array<string, int>
     */
    public function countGroupedByDaySince(\DateTimeImmutable $since): array
    {
        $messages = $this->createQueryBuilder('m')
            ->where('m.sentAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($messages as $message) {
            $day = $message->getSentAt()?->format('Y-m-d');
            if ($day === null) {
                continue;
            }
            $counts[$day] = ($counts[$day] ?? 0) + 1;
        }

        return $counts;
    }
}
