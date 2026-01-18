<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Enum\ModePaiement;
use App\Enum\StatutPaiement;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TransactionController extends AbstractController
{
    #[Route('/api/transactions', name: 'api_transactions_list', methods: ['GET'])]
    public function list(TransactionRepository $repository): JsonResponse
    {
        $transactions = array_map(
            fn(Transaction $transaction) => $this->serializeTransaction($transaction),
            $repository->findAll()
        );

        return $this->json($transactions);
    }

    #[Route('/api/transactions/{id}', name: 'api_transactions_show', methods: ['GET'])]
    public function show(int $id, TransactionRepository $repository): JsonResponse
    {
        $transaction = $repository->find($id);

        if (!$transaction) {
            return $this->errorResponse('Transaction not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeTransaction($transaction));
    }

    #[Route('/api/transactions', name: 'api_transactions_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        // Validation de la référence transaction
        $referenceTransaction = trim((string)($data['reference_transaction'] ?? ''));
        if ($referenceTransaction === '') {
            return $this->errorResponse('Reference transaction is required.', Response::HTTP_BAD_REQUEST);
        }

        // Validation du montant
        $montantTransaction = $this->parsePrice($data['montant_transaction'] ?? null, true, $error);
        if ($montantTransaction === null) {
            return $this->errorResponse($error ?? 'Invalid montant_transaction.', Response::HTTP_BAD_REQUEST);
        }

        // Validation de la date de paiement
        $dateDePaiement = $this->parseDateTime($data['date_de_paiement'] ?? null, $error);
        if ($dateDePaiement === null) {
            return $this->errorResponse($error ?? 'Invalid date_de_paiement.', Response::HTTP_BAD_REQUEST);
        }

        // Validation du statut paiement
        $statutPaiement = $this->parseStatutPaiement($data['statut_paiement'] ?? null, $error);
        if ($statutPaiement === null) {
            return $this->errorResponse($error ?? 'Invalid statut_paiement.', Response::HTTP_BAD_REQUEST);
        }

        // Validation du moyen de paiement (tableau)
        $moyenPaiement = $this->parseMoyenPaiement($data['moyen_paiement'] ?? null, $error);
        if ($moyenPaiement === null) {
            return $this->errorResponse($error ?? 'Invalid moyen_paiement.', Response::HTTP_BAD_REQUEST);
        }

        // Validation de la réservation (obligatoire)
        if (!isset($data['reservation_id'])) {
            return $this->errorResponse('Reservation ID is required.', Response::HTTP_BAD_REQUEST);
        }

        $reservation = $entityManager->getRepository(\App\Entity\Reservation::class)->find($data['reservation_id']);
        if (!$reservation) {
            return $this->errorResponse('Reservation not found.', Response::HTTP_BAD_REQUEST);
        }

        // Création de la transaction
        $transaction = (new Transaction())
            ->setReferenceTransaction($referenceTransaction)
            ->setMontantTransaction($montantTransaction)
            ->setDateDePaiement($dateDePaiement)
            ->setStatutPaiement($statutPaiement)
            ->setMoyenPaiement($moyenPaiement)
            ->setReservation($reservation);

        // ID compte Stripe (optionnel)
        if (isset($data['id_compte_stripe'])) {
            $idCompteStripe = $this->parseInt($data['id_compte_stripe'] ?? null, $error);
            if ($idCompteStripe !== null) {
                $transaction->setIdCompteStripe($idCompteStripe);
            }
        }

        // User (optionnel)
        if (isset($data['user_id'])) {
            $user = $entityManager->getRepository(\App\Entity\User::class)->find($data['user_id']);
            if (!$user) {
                return $this->errorResponse('User not found.', Response::HTTP_BAD_REQUEST);
            }
            $transaction->setUser($user);
        }

        $entityManager->persist($transaction);
        $entityManager->flush();

        return $this->json($this->serializeTransaction($transaction), Response::HTTP_CREATED);
    }

    #[Route('/api/transactions/{id}', name: 'api_transactions_update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        TransactionRepository $repository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $transaction = $repository->find($id);

        if (!$transaction) {
            return $this->errorResponse('Transaction not found.', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        $isPut = $request->getMethod() === 'PUT';

        if (array_key_exists('reference_transaction', $data) || $isPut) {
            $referenceTransaction = trim((string)($data['reference_transaction'] ?? ''));
            if ($referenceTransaction === '') {
                return $this->errorResponse('Reference transaction is required.', Response::HTTP_BAD_REQUEST);
            }
            $transaction->setReferenceTransaction($referenceTransaction);
        }

        if (array_key_exists('montant_transaction', $data) || $isPut) {
            $montantTransaction = $this->parsePrice($data['montant_transaction'] ?? null, true, $error);
            if ($montantTransaction === null) {
                return $this->errorResponse($error ?? 'Invalid montant_transaction.', Response::HTTP_BAD_REQUEST);
            }
            $transaction->setMontantTransaction($montantTransaction);
        }

        if (array_key_exists('date_de_paiement', $data) || $isPut) {
            $dateDePaiement = $this->parseDateTime($data['date_de_paiement'] ?? null, $error);
            if ($dateDePaiement === null) {
                return $this->errorResponse($error ?? 'Invalid date_de_paiement.', Response::HTTP_BAD_REQUEST);
            }
            $transaction->setDateDePaiement($dateDePaiement);
        }

        if (array_key_exists('statut_paiement', $data) || $isPut) {
            $statutPaiement = $this->parseStatutPaiement($data['statut_paiement'] ?? null, $error);
            if ($statutPaiement === null) {
                return $this->errorResponse($error ?? 'Invalid statut_paiement.', Response::HTTP_BAD_REQUEST);
            }
            $transaction->setStatutPaiement($statutPaiement);
        }

        if (array_key_exists('moyen_paiement', $data) || $isPut) {
            $moyenPaiement = $this->parseMoyenPaiement($data['moyen_paiement'] ?? null, $error);
            if ($moyenPaiement === null) {
                return $this->errorResponse($error ?? 'Invalid moyen_paiement.', Response::HTTP_BAD_REQUEST);
            }
            $transaction->setMoyenPaiement($moyenPaiement);
        }

        if (array_key_exists('id_compte_stripe', $data)) {
            $idCompteStripe = $this->parseInt($data['id_compte_stripe'] ?? null, $error);
            $transaction->setIdCompteStripe($idCompteStripe);
        }

        if (array_key_exists('reservation_id', $data) || $isPut) {
            if (!isset($data['reservation_id'])) {
                return $this->errorResponse('Reservation ID is required.', Response::HTTP_BAD_REQUEST);
            }
            $reservation = $entityManager->getRepository(\App\Entity\Reservation::class)->find($data['reservation_id']);
            if (!$reservation) {
                return $this->errorResponse('Reservation not found.', Response::HTTP_BAD_REQUEST);
            }
            $transaction->setReservation($reservation);
        }

        if (array_key_exists('user_id', $data)) {
            if ($data['user_id'] === null) {
                $transaction->setUser(null);
            } else {
                $user = $entityManager->getRepository(\App\Entity\User::class)->find($data['user_id']);
                if (!$user) {
                    return $this->errorResponse('User not found.', Response::HTTP_BAD_REQUEST);
                }
                $transaction->setUser($user);
            }
        }

        $entityManager->flush();

        return $this->json($this->serializeTransaction($transaction));
    }

    #[Route('/api/transactions/{id}', name: 'api_transactions_delete', methods: ['DELETE'])]
    public function delete(int $id, TransactionRepository $repository, EntityManagerInterface $entityManager): JsonResponse
    {
        $transaction = $repository->find($id);

        if (!$transaction) {
            return $this->errorResponse('Transaction not found.', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($transaction);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function decodeJson(Request $request): ?array
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $payload;
    }

    private function parsePrice(mixed $value, bool $required, ?string &$error): ?string
    {
        if ($value === null || $value === '') {
            if ($required) {
                $error = 'Price is required.';
            }
            return null;
        }

        if (!is_numeric($value)) {
            $error = 'Price must be numeric.';
            return null;
        }

        $price = (float)$value;
        if ($price < 0) {
            $error = 'Price must be positive.';
            return null;
        }

        return number_format($price, 2, '.', '');
    }

    private function parseDateTime(mixed $value, ?string &$error): ?\DateTime
    {
        if ($value === null || $value === '') {
            $error = 'DateTime is required.';
            return null;
        }

        try {
            return new \DateTime($value);
        } catch (\Exception $e) {
            $error = 'Invalid datetime format. Use ISO 8601 format (e.g., 2024-01-20T14:30:00).';
            return null;
        }
    }

    private function parseInt(mixed $value, ?string &$error): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            $error = 'Value must be an integer.';
            return null;
        }

        return (int)$value;
    }

    private function parseStatutPaiement(mixed $value, ?string &$error): ?StatutPaiement
    {
        if ($value === null || $value === '') {
            $error = 'Statut paiement is required.';
            return null;
        }

        try {
            return StatutPaiement::from($value);
        } catch (\ValueError $e) {
            $error = 'Invalid statut_paiement. Accepted values: ' . implode(', ', array_map(fn($case) => $case->value, StatutPaiement::cases()));
            return null;
        }
    }

    private function parseMoyenPaiement(mixed $value, ?string &$error): ?array
    {
        if ($value === null || !is_array($value)) {
            $error = 'Moyen paiement must be an array.';
            return null;
        }

        if (empty($value)) {
            $error = 'At least one moyen paiement is required.';
            return null;
        }

        $moyens = [];
        foreach ($value as $moyen) {
            try {
                $moyens[] = ModePaiement::from($moyen);
            } catch (\ValueError $e) {
                $error = 'Invalid moyen_paiement value: ' . $moyen . '. Accepted values: ' . implode(', ', array_map(fn($case) => $case->value, ModePaiement::cases()));
                return null;
            }
        }

        return $moyens;
    }

    private function serializeTransaction(Transaction $transaction): array
    {
        return [
            'id' => $transaction->getId(),
            'reference_transaction' => $transaction->getReferenceTransaction(),
            'montant_transaction' => $transaction->getMontantTransaction(),
            'date_de_paiement' => $transaction->getDateDePaiement()->format(\DateTimeInterface::ATOM),
            'statut_paiement' => $transaction->getStatutPaiement()->value,
            'moyen_paiement' => array_map(fn(ModePaiement $mode) => $mode->value, $transaction->getMoyenPaiement()),
            'id_compte_stripe' => $transaction->getIdCompteStripe(),
            'user_id' => $transaction->getUser()?->getId(),
            'reservation_id' => $transaction->getReservation()?->getId(),
        ];
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return $this->json(['error' => $message], $status);
    }
}