<?php

namespace App\Controller;

use App\Entity\Solde;
use App\Form\SoldeType;
use App\Repository\SoldeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/solde')]
final class SoldeController extends AbstractController
{
    #[Route('/new', name: 'app_solde_new', methods: ['GET', 'POST'])]
    public function newSolde(Request $request, EntityManagerInterface $entityManager): Response
    {
        $solde = new Solde();
        $entityManager->persist($solde);
        $entityManager->flush();

        return $this->json($this->serializeSolde($solde), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_solde_show', methods: ['GET'])]
    public function show(Solde $solde): Response
    {
        return $this->render('solde/show.html.twig', [
            'solde' => $solde,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_solde_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Solde $solde, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SoldeType::class, $solde);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_solde_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('solde/edit.html.twig', [
            'solde' => $solde,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_solde_delete', methods: ['POST'])]
    public function delete(Request $request, Solde $solde, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$solde->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($solde);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_solde_index', [], Response::HTTP_SEE_OTHER);
    }

    private function serializeSolde(Solde $solde): array
    {
        return [
            'id' => $solde->getId(),
            'montant' => $solde->getMontantSolde(),
            'user_id' => $solde->getUser()?->getId(),
        ];
    }

}
