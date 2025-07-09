<?php
namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Cours;
use App\Entity\Module;
use App\Entity\Chapitre;
use App\Entity\Classe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        // --- USERS ---
        $users = [];
        
        // Admin
        $admin = new User();
        $admin->setEmail("admin@example.com");
        $admin->setNom("Administrateur");
        $admin->setPrenom("Super");
        $admin->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->hasher->hashPassword($admin, 'password');
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

        // Enseignants
        $enseignants = [];
        foreach (range(1, 5) as $i) {
            $enseignant = new User();
            $enseignant->setEmail("enseignant$i@example.com");
            $enseignant->setNom("Enseignant $i");
            $enseignant->setPrenom("Prénom $i");
            $enseignant->setRoles(['ROLE_ENSEIGNANT']);
            $hashedPassword = $this->hasher->hashPassword($enseignant, 'password');
            $enseignant->setPassword($hashedPassword);
            $manager->persist($enseignant);
            $enseignants[] = $enseignant;
        }

        // Étudiants
        foreach (range(1, 20) as $i) {
            $user = new User();
            $user->setEmail("etudiant$i@example.com");
            $user->setNom("Étudiant $i");
            $user->setPrenom("Prénom $i");
            $user->setRoles(['ROLE_ETUDIANT']);
            $hashedPassword = $this->hasher->hashPassword($user, 'password');
            $user->setPassword($hashedPassword);
            $manager->persist($user);
            $users[] = $user;
        }

        // --- COURS ---
        $coursList = [];
        foreach (range(1, 20) as $i) {
            $cours = new Cours();
            $cours->setTitre("Cours $i");
            $cours->setDescription("Description du cours $i sur Symfony et PHP...");
            $cours->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($cours);
            $coursList[] = $cours;
        }

        // --- MODULES ---
        $modules = [];
        foreach (range(1, 20) as $i) {
            $module = new Module();
            $module->setTitre("Module $i");
            $module->setCours($coursList[array_rand($coursList)]);
            $manager->persist($module);
            $modules[] = $module;
        }

        // --- CLASSES ---
        $classes = [];
        foreach (range(1, 5) as $i) {
            $classe = new Classe();
            $classe->setNom("Classe $i");
            $classe->setDescription("Description de la classe $i");
            $classe->setCreatedAt(new \DateTimeImmutable());
            
            // Assigner des étudiants à la classe
            $etudiantsClasse = array_slice($users, ($i-1)*4, 4);
            foreach ($etudiantsClasse as $etudiant) {
                $classe->addEtudiant($etudiant);
            }
            
            // Assigner un enseignant à la classe
            if (isset($enseignants[$i-1])) {
                $classe->addProfesseur($enseignants[$i-1]);
            }
            
            // Assigner quelques cours à la classe
            $coursClasse = array_slice($coursList, ($i-1)*3, 3);
            foreach ($coursClasse as $cours) {
                $classe->addCours($cours);
            }
            
            $manager->persist($classe);
            $classes[] = $classe;
        }

        // --- CHAPITRES ---
        foreach (range(1, 20) as $i) {
            $chapitre = new Chapitre();
            $chapitre->setTitre("Chapitre $i");
            $chapitre->setContenu("Contenu détaillé du chapitre $i...");
            $chapitre->setFichierMedia(null);
            $chapitre->setModule($modules[array_rand($modules)]);
            $manager->persist($chapitre);
        }

        $manager->flush();
    }
}
