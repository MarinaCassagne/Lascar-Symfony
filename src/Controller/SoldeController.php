<?php

namespace App\Controller;

use App\Entity\Solde;
use App\Entity\User;
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
    #[Route('api/newSolde', name: 'app_solde_new', methods: ['POST'])]
    public function newSolde(Request $request, EntityManagerInterface $entityManager, User $user): Response
    {
        $solde = (new Solde()
            ->setUser($user)
            ->setMontantSolde("0"));

        $entityManager->persist($solde);
        $entityManager->flush();

        return $this->json($this->serializeSolde($solde), Response::HTTP_CREATED);
    }

    #[Route('api/{userId}', name: 'app_solde_show', methods: ['GET'])]
    public function solde(
        int $userId,
        SoldeRepository $soldeRepository
    ): Response {
        $solde = $soldeRepository->findOneBy([
            'user' => $userId
        ]);

        if (!$solde) {
            return $this->json([
                'message' => 'Solde introuvable'
            ], 404);
        }

        return $this->json([
            'userId' => $userId,
            'solde' => $solde->getMontant()
        ]);
    }


    #[Route('/{userId}/edit', name: 'app_solde_edit', methods: ['PUT'])]
    public function edit(
        int $userId,
        Request $request,
        SoldeRepository $soldeRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $solde = $soldeRepository->findOneBy([
            'user' => $userId
        ]);

        if (!$solde) {
            return $this->json(['message' => 'Solde introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['montant'])) {
            return $this->json(['message' => 'Montant requis'], 400);
        }

        $montant = (float) $data['montant'];

        if ($montant < 0) {
            return $this->json(['message' => 'Montant invalide'], 400);
        }

        $solde->setMontant($montant);
        $entityManager->flush();

        return $this->json([
            'userId' => $userId,
            'nouveauSolde' => $solde->getMontant()
        ]);
    }

    #[Route('api/solde/{id}', name: 'app_solde_delete', methods: ['DELETE'])]
    public function delete(
        Solde $solde,
        EntityManagerInterface $entityManager
    ): Response {
        $entityManager->remove($solde);
        $entityManager->flush();

        return $this->json(null, 204); // No Content
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
