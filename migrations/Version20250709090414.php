<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250709090414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE classe (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE classe_etudiants (classe_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_132842CC8F5EA509 (classe_id), INDEX IDX_132842CCA76ED395 (user_id), PRIMARY KEY(classe_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE classe_professeurs (classe_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_3ED383188F5EA509 (classe_id), INDEX IDX_3ED38318A76ED395 (user_id), PRIMARY KEY(classe_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE classe_cours (classe_id INT NOT NULL, cours_id INT NOT NULL, INDEX IDX_B4BDD8A48F5EA509 (classe_id), INDEX IDX_B4BDD8A47ECF78B0 (cours_id), PRIMARY KEY(classe_id, cours_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE classe_etudiants ADD CONSTRAINT FK_132842CC8F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classe_etudiants ADD CONSTRAINT FK_132842CCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classe_professeurs ADD CONSTRAINT FK_3ED383188F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classe_professeurs ADD CONSTRAINT FK_3ED38318A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classe_cours ADD CONSTRAINT FK_B4BDD8A48F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classe_cours ADD CONSTRAINT FK_B4BDD8A47ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cours ADD CONSTRAINT FK_FDCA8C9CB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_FDCA8C9CB03A8386 ON cours (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classe_etudiants DROP FOREIGN KEY FK_132842CC8F5EA509');
        $this->addSql('ALTER TABLE classe_etudiants DROP FOREIGN KEY FK_132842CCA76ED395');
        $this->addSql('ALTER TABLE classe_professeurs DROP FOREIGN KEY FK_3ED383188F5EA509');
        $this->addSql('ALTER TABLE classe_professeurs DROP FOREIGN KEY FK_3ED38318A76ED395');
        $this->addSql('ALTER TABLE classe_cours DROP FOREIGN KEY FK_B4BDD8A48F5EA509');
        $this->addSql('ALTER TABLE classe_cours DROP FOREIGN KEY FK_B4BDD8A47ECF78B0');
        $this->addSql('DROP TABLE classe');
        $this->addSql('DROP TABLE classe_etudiants');
        $this->addSql('DROP TABLE classe_professeurs');
        $this->addSql('DROP TABLE classe_cours');
        $this->addSql('ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9CB03A8386');
        $this->addSql('DROP INDEX IDX_FDCA8C9CB03A8386 ON cours');
        $this->addSql('ALTER TABLE cours DROP created_by_id');
    }
}
