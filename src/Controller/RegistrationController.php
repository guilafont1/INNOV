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
        // Rediriger vers la page de connexion car l'inscription est réservée aux administrateurs
        $this->addFlash('info', 'L\'inscription est réservée aux administrateurs. Veuillez vous connecter ou contacter un administrateur pour créer un compte.');
        return $this->redirectToRoute('app_login');
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
