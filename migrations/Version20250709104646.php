<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250709104646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD derniere_connexion DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD derniere_deconnexion DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP last_login_at, DROP last_logout_at, DROP last_login_ip');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD last_login_at DATETIME DEFAULT NULL, ADD last_logout_at DATETIME DEFAULT NULL, ADD last_login_ip VARCHAR(45) DEFAULT NULL, DROP derniere_connexion, DROP derniere_deconnexion');
    }
}
