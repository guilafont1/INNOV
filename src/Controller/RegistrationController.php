<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationForm;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct()
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération des données du formulaire
            $plainPassword = $form->get('plainPassword')->getData();
            $roleChoice = $form->get('roleChoice')->getData();

            // Configuration du mot de passe
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setPasswordUpdatedAt(new \DateTime());
            
            // Attribution du rôle
            $user->setRoles([$roleChoice]);

            // Validation de l'entité
            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            try {
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', sprintf(
                    'Félicitations %s %s ! Votre compte a été créé avec succès.',
                    $user->getPrenom(),
                    $user->getNom()
                ));
                
                // Redirection vers la page de succès
                return $this->render('registration/success.html.twig', [
                    'user' => $user
                ]);
                
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('error', 'Un compte avec cet email existe déjà. Veuillez utiliser une autre adresse email ou vous connecter.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur inattendue s\'est produite lors de l\'inscription. Veuillez réessayer plus tard.');
                // Log de l'erreur pour le débogage (optionnel)
                error_log('Registration error: ' . $e->getMessage());
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/check-email', name: 'app_check_email', methods: ['POST'])]
    public function checkEmail(Request $request, EntityManagerInterface $entityManager): Response
    {
        $email = $request->request->get('email');
        
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'valid' => false,
                'message' => 'Format d\'email invalide'
            ]);
        }

        $userRepository = $entityManager->getRepository(User::class);
        $existingUser = $userRepository->findOneBy(['email' => $email]);

        if ($existingUser) {
            return $this->json([
                'valid' => false,
                'message' => 'Un compte avec cet email existe déjà'
            ]);
        }

        return $this->json([
            'valid' => true,
            'message' => '✓ Email disponible'
        ]);
    }

}
