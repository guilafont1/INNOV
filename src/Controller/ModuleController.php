<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Module;
use App\Form\ModuleType;
use App\Repository\ModuleRepository;

class ModuleController extends AbstractController
{
    #[Route('/module', name: 'app_module_index')]
    public function index(ModuleRepository $moduleRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');
        
        try {
            $modules = $moduleRepository->findAll();
            
            if (empty($modules)) {
                $this->addFlash('info', 'Aucun module disponible pour le moment.');
            }
            
            return $this->render('module/index.html.twig', [
                'modules' => $modules,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du chargement des modules.');
            return $this->render('module/index.html.twig', ['modules' => []]);
        }
    }

    #[Route('/module/{id}', name: 'app_module_show', requirements: ['id' => '\d+'])]
    public function show(Module $module): Response
    {
        return $this->render('module/show.html.twig', [
            'module' => $module,
        ]);
    }

    #[Route('/module/new', name: 'app_module_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        $module = new Module();
        $form = $this->createForm(ModuleType::class, $module);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->persist($module);
                $em->flush();

                $this->addFlash('success', 'Module créé avec succès !');
                return $this->redirectToRoute('enseignant_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du module.');
            }
        }

        return $this->render('module/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/module/{id}/edit', name: 'app_module_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Module $module, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        $form = $this->createForm(ModuleType::class, $module);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();

                $this->addFlash('success', 'Module modifié avec succès !');
                return $this->redirectToRoute('app_module_show', ['id' => $module->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification du module.');
            }
        }

        return $this->render('module/edit.html.twig', [
            'module' => $module,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/module/{id}/delete', name: 'app_module_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Module $module, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENSEIGNANT');

        if ($this->isCsrfTokenValid('delete'.$module->getId(), $request->request->get('_token'))) {
            try {
                $em->remove($module);
                $em->flush();

                $this->addFlash('success', 'Module supprimé avec succès !');
                return $this->redirectToRoute('app_module_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression du module.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_module_show', ['id' => $module->getId()]);
    }
}
