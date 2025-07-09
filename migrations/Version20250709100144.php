<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250709100144 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE note (id INT AUTO_INCREMENT NOT NULL, etudiant_id INT NOT NULL, module_id INT NOT NULL, professeur_id INT DEFAULT NULL, note NUMERIC(5, 2) NOT NULL, note_max NUMERIC(5, 2) NOT NULL, commentaire VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_CFBDFA14DDEAB1A3 (etudiant_id), INDEX IDX_CFBDFA14AFC2B591 (module_id), INDEX IDX_CFBDFA14BAB22EE9 (professeur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14BAB22EE9 FOREIGN KEY (professeur_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14DDEAB1A3');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14AFC2B591');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14BAB22EE9');
        $this->addSql('DROP TABLE note');
    }
}
