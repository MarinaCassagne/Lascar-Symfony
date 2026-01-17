<?php

namespace App\Controller;

use App\Entity\Moderation;
use App\Form\ModerationType;
use App\Repository\ModerationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderation')]
final class ModerationController extends AbstractController
{
    #[Route(name: 'app_moderation_index', methods: ['GET'])]
    public function index(ModerationRepository $moderationRepository): Response
    {
        return $this->render('moderation/index.html.twig', [
            'moderations' => $moderationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_moderation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $moderation = new Moderation();
        $form = $this->createForm(ModerationType::class, $moderation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($moderation);
            $entityManager->flush();

            return $this->redirectToRoute('app_moderation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderation/new.html.twig', [
            'moderation' => $moderation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_moderation_show', methods: ['GET'])]
    public function show(Moderation $moderation): Response
    {
        return $this->render('moderation/show.html.twig', [
            'moderation' => $moderation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_moderation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Moderation $moderation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ModerationType::class, $moderation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_moderation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderation/edit.html.twig', [
            'moderation' => $moderation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_moderation_delete', methods: ['POST'])]
    public function delete(Request $request, Moderation $moderation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$moderation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($moderation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_moderation_index', [], Response::HTTP_SEE_OTHER);
    }
}
