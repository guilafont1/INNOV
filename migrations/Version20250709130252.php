<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250709130252 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE calendrier ADD enseignant_id INT DEFAULT NULL, ADD classe_id INT DEFAULT NULL, ADD date_fin DATETIME DEFAULT NULL, ADD lieu VARCHAR(255) DEFAULT NULL, ADD type VARCHAR(50) NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE date_heure date_debut DATETIME NOT NULL');
        $this->addSql('ALTER TABLE calendrier ADD CONSTRAINT FK_B2753CB9E455FCC0 FOREIGN KEY (enseignant_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE calendrier ADD CONSTRAINT FK_B2753CB98F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)');
        $this->addSql('CREATE INDEX IDX_B2753CB9E455FCC0 ON calendrier (enseignant_id)');
        $this->addSql('CREATE INDEX IDX_B2753CB98F5EA509 ON calendrier (classe_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE calendrier DROP FOREIGN KEY FK_B2753CB9E455FCC0');
        $this->addSql('ALTER TABLE calendrier DROP FOREIGN KEY FK_B2753CB98F5EA509');
        $this->addSql('DROP INDEX IDX_B2753CB9E455FCC0 ON calendrier');
        $this->addSql('DROP INDEX IDX_B2753CB98F5EA509 ON calendrier');
        $this->addSql('ALTER TABLE calendrier DROP enseignant_id, DROP classe_id, DROP date_fin, DROP lieu, DROP type, CHANGE description description LONGTEXT NOT NULL, CHANGE date_debut date_heure DATETIME NOT NULL');
    }
}
