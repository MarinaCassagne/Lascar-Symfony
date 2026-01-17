<?php

namespace App\Controller;

use App\Entity\EtapeTrajet;
use App\Form\EtapeTrajetType;
use App\Repository\EtapeTrajetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/etape/trajet')]
final class EtapeTrajetController extends AbstractController
{
    #[Route(name: 'app_etape_trajet_index', methods: ['GET'])]
    public function index(EtapeTrajetRepository $etapeTrajetRepository): Response
    {
        return $this->render('etape_trajet/index.html.twig', [
            'etape_trajets' => $etapeTrajetRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_etape_trajet_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $etapeTrajet = new EtapeTrajet();
        $form = $this->createForm(EtapeTrajetType::class, $etapeTrajet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($etapeTrajet);
            $entityManager->flush();

            return $this->redirectToRoute('app_etape_trajet_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('etape_trajet/new.html.twig', [
            'etape_trajet' => $etapeTrajet,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_etape_trajet_show', methods: ['GET'])]
    public function show(EtapeTrajet $etapeTrajet): Response
    {
        return $this->render('etape_trajet/show.html.twig', [
            'etape_trajet' => $etapeTrajet,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_etape_trajet_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EtapeTrajet $etapeTrajet, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EtapeTrajetType::class, $etapeTrajet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_etape_trajet_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('etape_trajet/edit.html.twig', [
            'etape_trajet' => $etapeTrajet,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_etape_trajet_delete', methods: ['POST'])]
    public function delete(Request $request, EtapeTrajet $etapeTrajet, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$etapeTrajet->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($etapeTrajet);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_etape_trajet_index', [], Response::HTTP_SEE_OTHER);
    }
}
