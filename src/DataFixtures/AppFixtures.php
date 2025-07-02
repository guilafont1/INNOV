<?php
namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Cours;
use App\Entity\Module;
use App\Entity\Chapitre;
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
        foreach (range(1, 20) as $i) {
            $user = new User();
            $user->setEmail("user$i@example.com");
            $user->setNom("Utilisateur $i");
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
