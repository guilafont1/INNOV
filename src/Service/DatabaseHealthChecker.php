<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

class DatabaseHealthChecker
{
    private ?bool $connected = null;

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function isConnected(): bool
    {
        if ($this->connected !== null) {
            return $this->connected;
        }

        try {
            $this->connection->executeQuery(
                $this->connection->getDatabasePlatform()->getDummySelectSQL()
            )->fetchOne();
            $this->connected = true;
        } catch (\Throwable) {
            $this->connected = false;
        }

        return $this->connected;
    }
}
