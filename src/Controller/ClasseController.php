<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Module;
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

    #[Route('/enseignant/classe/{id}/notes', name: 'enseignant_classe_notes', methods: ['GET', 'POST'])]
    public function notes(
        Classe $classe, 
        Request $request,
        NoteRepository $noteRepository,
        ModuleRepository $moduleRepository,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        // Vérifier que l'utilisateur est professeur de cette classe
        if (!$classe->getProfesseurs()->contains($this->getUser())) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas professeur de cette classe.');
        }

        $etudiants = $classe->getEtudiants();
        $modules = $this->getModulesForClasse($classe);
        $moduleIds = array_map(static fn ($m) => $m->getId(), $modules);
        $etudiantIds = [];
        foreach ($etudiants as $etudiant) {
            $etudiantIds[$etudiant->getId()] = true;
        }

        $selectedModuleId = (int) $request->query->get('module', 0);

        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token');
            if (!$this->isCsrfTokenValid('notes_save_' . $classe->getId(), $token)) {
                $this->addFlash('error', 'Session expirée. Veuillez réessayer.');
                return $this->redirectToRoute('enseignant_classe_notes', [
                    'id' => $classe->getId(),
                ]);
            }

            $moduleId = (int) $request->request->get('module_id');
            $notes = $request->request->all('notes') ?? [];
            $savedCount = 0;

            if ($moduleId <= 0) {
                $this->addFlash('error', 'Veuillez sélectionner un module.');
            } elseif (!in_array($moduleId, $moduleIds, true)) {
                $this->addFlash('error', 'Module invalide pour cette classe.');
            } elseif (!is_array($notes)) {
                $this->addFlash('error', 'Données de notes invalides.');
            } else {
                $module = $moduleRepository->find($moduleId);
                if ($module === null) {
                    $this->addFlash('error', 'Module introuvable.');
                } else {
                    foreach ($notes as $etudiantId => $noteData) {
                        if (!is_array($noteData)) {
                            continue;
                        }

                        $etudiantId = (int) $etudiantId;
                        if (!isset($etudiantIds[$etudiantId])) {
                            continue;
                        }

                        $noteValue = $this->normalizeNoteValue($noteData['note'] ?? null);
                        $noteMaxValue = $this->normalizeNoteValue($noteData['note_max'] ?? null);

                        if ($noteValue === null) {
                            continue;
                        }

                        if ($noteMaxValue === null || $noteMaxValue <= 0) {
                            $noteMaxValue = '20';
                        }

                        if ((float) $noteValue < 0 || (float) $noteValue > (float) $noteMaxValue) {
                            continue;
                        }

                        $etudiant = $userRepository->find($etudiantId);
                        if ($etudiant === null) {
                            continue;
                        }

                        $commentaire = trim((string) ($noteData['commentaire'] ?? ''));
                        $noteExistante = $noteRepository->findByEtudiantAndModule($etudiant, $module);

                        if ($noteExistante) {
                            $noteExistante->setNote((string) $noteValue);
                            $noteExistante->setNoteMax((string) $noteMaxValue);
                            $noteExistante->setCommentaire($commentaire !== '' ? $commentaire : null);
                            $noteExistante->setProfesseur($this->getUser());
                        } else {
                            $note = new Note();
                            $note->setEtudiant($etudiant);
                            $note->setModule($module);
                            $note->setNote((string) $noteValue);
                            $note->setNoteMax((string) $noteMaxValue);
                            $note->setCommentaire($commentaire !== '' ? $commentaire : null);
                            $note->setProfesseur($this->getUser());
                            $em->persist($note);
                        }

                        ++$savedCount;
                    }

                    if ($savedCount > 0) {
                        $em->flush();
                        $this->addFlash('success', $savedCount === 1 ? '1 note enregistrée.' : $savedCount . ' notes enregistrées.');
                        $selectedModuleId = $moduleId;
                    } else {
                        $this->addFlash('error', 'Aucune note valide à enregistrer. Saisissez au moins une note.');
                        $selectedModuleId = $moduleId;
                    }
                }
            }

            return $this->redirectToRoute('enseignant_classe_notes', [
                'id' => $classe->getId(),
                'module' => $selectedModuleId > 0 ? $selectedModuleId : null,
            ]);
        }

        // Récupérer les notes existantes
        $notesExistantes = [];
        $notesPrefill = [];
        foreach ($modules as $module) {
            $moduleNotes = $noteRepository->findByModule($module);
            $notesExistantes[$module->getId()] = $moduleNotes;
            $notesPrefill[$module->getId()] = [];
            foreach ($moduleNotes as $note) {
                $notesPrefill[$module->getId()][$note->getEtudiant()->getId()] = [
                    'note' => $note->getNote(),
                    'note_max' => $note->getNoteMax(),
                    'commentaire' => $note->getCommentaire(),
                ];
            }
        }

        return $this->render('enseignant/classes/notes.html.twig', [
            'classe' => $classe,
            'etudiants' => $etudiants,
            'modules' => $modules,
            'notesExistantes' => $notesExistantes,
            'notesPrefill' => $notesPrefill,
            'selectedModuleId' => $selectedModuleId,
        ]);
    }

    /**
     * @return Module[]
     */
    private function getModulesForClasse(Classe $classe): array
    {
        $modules = [];
        foreach ($classe->getCours() as $cours) {
            foreach ($cours->getModules() as $module) {
                $modules[] = $module;
            }
        }

        return $modules;
    }

    private function normalizeNoteValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (string) $value;
    }

    #[Route('/enseignant/classe/{id}/etudiants', name: 'enseignant_classe_etudiants')]
    public function gererEtudiants(
        Classe $classe,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN_ECOLE');

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
