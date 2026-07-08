<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(): Response
    {
        // Rediriger vers la page de connexion car l'inscription est réservée aux administrateurs
        $this->addFlash('info', 'L\'inscription est réservée aux administrateurs. Veuillez vous connecter ou contacter un administrateur pour créer un compte.');
        return $this->redirectToRoute('app_login');
    }

}
