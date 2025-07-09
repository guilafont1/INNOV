<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Note;
use App\Repository\ClasseRepository;
use App\Repository\UserRepository;
use App\Repository\NoteRepository;
use App\Repository\ModuleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class ClasseController extends AbstractController
{
    #[Route('/enseignant/classes', name: 'enseignant_classes')]
    public function index(ClasseRepository $classeRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        $user = $this->getUser();
        
        // Récupérer les classes où l'utilisateur est professeur
        $classes = $classeRepository->findByProfesseur($user);

        return $this->render('enseignant/classes/index.html.twig', [
            'classes' => $classes,
        ]);
    }

    #[Route('/enseignant/classe/{id}', name: 'enseignant_classe_show')]
    public function show(Classe $classe, NoteRepository $noteRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        // Vérifier que l'utilisateur est professeur de cette classe
        if (!$classe->getProfesseurs()->contains($this->getUser())) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas professeur de cette classe.');
        }

        $etudiants = $classe->getEtudiants();
        $cours = $classe->getCours();

        // Récupérer les notes pour chaque étudiant et chaque module
        $notesParEtudiant = [];
        foreach ($etudiants as $etudiant) {
            $notesParEtudiant[$etudiant->getId()] = $noteRepository->findByEtudiant($etudiant);
        }

        return $this->render('enseignant/classes/show.html.twig', [
            'classe' => $classe,
            'etudiants' => $etudiants,
            'cours' => $cours,
            'notesParEtudiant' => $notesParEtudiant,
        ]);
    }

    #[Route('/enseignant/classe/{id}/notes', name: 'enseignant_classe_notes')]
    public function notes(
        Classe $classe, 
        Request $request,
        NoteRepository $noteRepository,
        ModuleRepository $moduleRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        // Vérifier que l'utilisateur est professeur de cette classe
        if (!$classe->getProfesseurs()->contains($this->getUser())) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas professeur de cette classe.');
        }

        if ($request->isMethod('POST')) {
            $moduleId = $request->request->get('module_id');
            $notes = $request->request->all('notes') ?? [];
            
            if ($moduleId && is_array($notes)) {
                $module = $moduleRepository->find($moduleId);
                
                foreach ($notes as $etudiantId => $noteData) {
                    if (is_array($noteData) && !empty($noteData['note']) && !empty($noteData['note_max'])) {
                        $etudiant = $em->getRepository('App:User')->find($etudiantId);
                        
                        // Vérifier si une note existe déjà
                        $noteExistante = $noteRepository->findByEtudiantAndModule($etudiant, $module);
                        
                        if ($noteExistante) {
                            $noteExistante->setNote($noteData['note']);
                            $noteExistante->setNoteMax($noteData['note_max']);
                            $noteExistante->setCommentaire($noteData['commentaire'] ?? null);
                        } else {
                            $note = new Note();
                            $note->setEtudiant($etudiant);
                            $note->setModule($module);
                            $note->setNote($noteData['note']);
                            $note->setNoteMax($noteData['note_max']);
                            $note->setCommentaire($noteData['commentaire'] ?? null);
                            $note->setProfesseur($this->getUser());
                            
                            $em->persist($note);
                        }
                    }
                }
                
                $em->flush();
                $this->addFlash('success', 'Notes enregistrées avec succès !');
            }
        }

        $etudiants = $classe->getEtudiants();
        $modules = [];
        
        // Récupérer tous les modules des cours de la classe
        foreach ($classe->getCours() as $cours) {
            foreach ($cours->getModules() as $module) {
                $modules[] = $module;
            }
        }

        // Récupérer les notes existantes
        $notesExistantes = [];
        foreach ($modules as $module) {
            $notesExistantes[$module->getId()] = $noteRepository->findByModule($module);
        }

        return $this->render('enseignant/classes/notes.html.twig', [
            'classe' => $classe,
            'etudiants' => $etudiants,
            'modules' => $modules,
            'notesExistantes' => $notesExistantes,
        ]);
    }

    #[Route('/enseignant/classe/{id}/etudiants', name: 'enseignant_classe_etudiants')]
    public function gererEtudiants(
        Classe $classe,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        // Vérifier que l'utilisateur est professeur de cette classe
        if (!$classe->getProfesseurs()->contains($this->getUser())) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas professeur de cette classe.');
        }

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            $etudiantId = $request->request->get('etudiant_id');
            
            if ($etudiantId) {
                $etudiant = $userRepository->find($etudiantId);
                
                if ($action === 'add' && $etudiant && in_array('ROLE_ETUDIANT', $etudiant->getRoles())) {
                    $classe->addEtudiant($etudiant);
                    $this->addFlash('success', 'Étudiant ajouté à la classe.');
                } elseif ($action === 'remove' && $etudiant) {
                    $classe->removeEtudiant($etudiant);
                    $this->addFlash('success', 'Étudiant retiré de la classe.');
                }
                
                $em->flush();
            }
        }

        // Récupérer tous les étudiants disponibles
        $tousLesEtudiants = $userRepository->findByRole('ROLE_ETUDIANT');
        $etudiantsDisponibles = array_filter($tousLesEtudiants, function($etudiant) use ($classe) {
            return !$classe->getEtudiants()->contains($etudiant);
        });

        return $this->render('enseignant/classes/etudiants.html.twig', [
            'classe' => $classe,
            'etudiantsDisponibles' => $etudiantsDisponibles,
        ]);
    }
}
