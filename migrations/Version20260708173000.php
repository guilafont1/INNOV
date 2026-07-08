<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260708173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Progression updatedAt + dernier chapitre + progression_chapitre join table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE progression ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD dernier_chapitre_id INT DEFAULT NULL');

        $this->addSql('ALTER TABLE progression ADD CONSTRAINT FK_progression_dernier_chapitre FOREIGN KEY (dernier_chapitre_id) REFERENCES chapitre (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_progression_dernier_chapitre ON progression (dernier_chapitre_id)');

        $this->addSql('CREATE TABLE progression_chapitre (progression_id INT NOT NULL, chapitre_id INT NOT NULL, INDEX IDX_progression_chapitre_progression (progression_id), INDEX IDX_progression_chapitre_chapitre (chapitre_id), PRIMARY KEY(progression_id, chapitre_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE progression_chapitre ADD CONSTRAINT FK_progression_chapitre_progression FOREIGN KEY (progression_id) REFERENCES progression (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE progression_chapitre ADD CONSTRAINT FK_progression_chapitre_chapitre FOREIGN KEY (chapitre_id) REFERENCES chapitre (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE progression DROP FOREIGN KEY FK_progression_dernier_chapitre');
        $this->addSql('ALTER TABLE progression DROP dernier_chapitre_id, DROP updated_at');

        $this->addSql('DROP TABLE progression_chapitre');
    }
}

