<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Apply ON DELETE actions for safer cascades.
 */
final class Version20260708170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set ON DELETE behaviors for key relations (cascade / set null).';
    }

    public function up(Schema $schema): void
    {
        // Module.cours -> CASCADE
        $this->addSql('ALTER TABLE module DROP FOREIGN KEY FK_C2426287ECF78B0');
        $this->addSql('ALTER TABLE module ADD CONSTRAINT FK_C2426287ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');

        // Chapitre.module -> CASCADE
        $this->addSql('ALTER TABLE chapitre DROP FOREIGN KEY FK_8C62B025AFC2B591');
        $this->addSql('ALTER TABLE chapitre ADD CONSTRAINT FK_8C62B025AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id) ON DELETE CASCADE');

        // Note relations
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14DDEAB1A3');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES user (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14AFC2B591');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14BAB22EE9');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14BAB22EE9 FOREIGN KEY (professeur_id) REFERENCES user (id) ON DELETE SET NULL');

        // Calendrier relations
        $this->addSql('ALTER TABLE calendrier DROP FOREIGN KEY FK_B2753CB9E455FCC0');
        $this->addSql('ALTER TABLE calendrier ADD CONSTRAINT FK_B2753CB9E455FCC0 FOREIGN KEY (enseignant_id) REFERENCES user (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE calendrier DROP FOREIGN KEY FK_B2753CB98F5EA509');
        $this->addSql('ALTER TABLE calendrier ADD CONSTRAINT FK_B2753CB98F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE calendrier DROP FOREIGN KEY FK_B2753CB97ECF78B0');
        $this->addSql('ALTER TABLE calendrier ADD CONSTRAINT FK_B2753CB97ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');

        // Progression relations
        $this->addSql('ALTER TABLE progression DROP FOREIGN KEY FK_D5B25073A76ED395');
        $this->addSql('ALTER TABLE progression ADD CONSTRAINT FK_D5B25073A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE progression DROP FOREIGN KEY FK_D5B250737ECF78B0');
        $this->addSql('ALTER TABLE progression ADD CONSTRAINT FK_D5B250737ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');

        // Forum post relations
        $this->addSql('ALTER TABLE forum_post DROP FOREIGN KEY FK_996BCC5A60BB6FE6');
        $this->addSql('ALTER TABLE forum_post ADD CONSTRAINT FK_996BCC5A60BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE forum_post DROP FOREIGN KEY FK_996BCC5A7ECF78B0');
        $this->addSql('ALTER TABLE forum_post ADD CONSTRAINT FK_996BCC5A7ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');

        // Message relations
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F10335F61');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F10335F61 FOREIGN KEY (expediteur_id) REFERENCES user (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FA4F84F6E');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FA4F84F6E FOREIGN KEY (destinataire_id) REFERENCES user (id) ON DELETE CASCADE');

        // Evaluation relations
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A5757ECF78B0');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A5757ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575B03A8386');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');

        // Cours.createdBy -> SET NULL
        $this->addSql('ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9CB03A8386');
        $this->addSql('ALTER TABLE cours ADD CONSTRAINT FK_FDCA8C9CB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // Module.cours -> remove ON DELETE
        $this->addSql('ALTER TABLE module DROP FOREIGN KEY FK_C2426287ECF78B0');
        $this->addSql('ALTER TABLE module ADD CONSTRAINT FK_C2426287ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');

        // Chapitre.module -> remove ON DELETE
        $this->addSql('ALTER TABLE chapitre DROP FOREIGN KEY FK_8C62B025AFC2B591');
        $this->addSql('ALTER TABLE chapitre ADD CONSTRAINT FK_8C62B025AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');

        // Note relations
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14DDEAB1A3');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES user (id)');

        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14AFC2B591');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');

        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14BAB22EE9');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14BAB22EE9 FOREIGN KEY (professeur_id) REFERENCES user (id)');

        // Calendrier relations
        $this->addSql('ALTER TABLE calendrier DROP FOREIGN KEY FK_B2753CB9E455FCC0');
        $this->addSql('ALTER TABLE calendrier ADD CONSTRAINT FK_B2753CB9E455FCC0 FOREIGN KEY (enseignant_id) REFERENCES user (id)');

        $this->addSql('ALTER TABLE calendrier DROP FOREIGN KEY FK_B2753CB98F5EA509');
        $this->addSql('ALTER TABLE calendrier ADD CONSTRAINT FK_B2753CB98F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)');

        $this->addSql('ALTER TABLE calendrier DROP FOREIGN KEY FK_B2753CB97ECF78B0');
        $this->addSql('ALTER TABLE calendrier ADD CONSTRAINT FK_B2753CB97ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');

        // Progression relations
        $this->addSql('ALTER TABLE progression DROP FOREIGN KEY FK_D5B25073A76ED395');
        $this->addSql('ALTER TABLE progression ADD CONSTRAINT FK_D5B25073A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');

        $this->addSql('ALTER TABLE progression DROP FOREIGN KEY FK_D5B250737ECF78B0');
        $this->addSql('ALTER TABLE progression ADD CONSTRAINT FK_D5B250737ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');

        // Forum post relations
        $this->addSql('ALTER TABLE forum_post DROP FOREIGN KEY FK_996BCC5A60BB6FE6');
        $this->addSql('ALTER TABLE forum_post ADD CONSTRAINT FK_996BCC5A60BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id)');

        $this->addSql('ALTER TABLE forum_post DROP FOREIGN KEY FK_996BCC5A7ECF78B0');
        $this->addSql('ALTER TABLE forum_post ADD CONSTRAINT FK_996BCC5A7ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');

        // Message relations
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F10335F61');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F10335F61 FOREIGN KEY (expediteur_id) REFERENCES user (id)');

        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FA4F84F6E');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FA4F84F6E FOREIGN KEY (destinataire_id) REFERENCES user (id)');

        // Evaluation relations
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A5757ECF78B0');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A5757ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');

        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575B03A8386');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');

        // Cours.createdBy -> remove ON DELETE
        $this->addSql('ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9CB03A8386');
        $this->addSql('ALTER TABLE cours ADD CONSTRAINT FK_FDCA8C9CB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
    }
}

