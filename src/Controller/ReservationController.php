<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\User;
use App\Enum\StatutReservation;
use App\Repository\ReservationRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
// use App\Controller\Trajet;

class ReservationController extends AbstractController
{
    #[Route('/api/reservations', name: 'api_reservations_list', methods: ['GET'])]
    public function reservations(ReservationRepository $repository): JsonResponse
    {
        $reservations = array_map(
            fn(Reservation $reservation) => $this->serializeReservation($reservation),
            $repository->findAll()
        );

        return $this->json($reservations);
    }

    #[Route('/api/reservations/{id}', name: 'api_reservations_show', methods: ['GET'])]
    public function reservationById(int $id, ReservationRepository $repository): JsonResponse
    {
        $reservation = $repository->find($id);

        if (!$reservation) {
            return $this->errorResponse('Reservation not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeReservation($reservation));
    }

    #[Route('/api/reserver', name: 'api_reservations_create', methods: ['POST'])]
    public function reserver(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        // Validation du numéro de réservation
        $numeroReservation = trim((string) ($data['numero_reservation'] ?? ''));
        if ($numeroReservation === '') {
            return $this->errorResponse('Numero de reservation is required.', Response::HTTP_BAD_REQUEST);
        }

        // Validation des dates
        $dateReservation = new \DateTime();

        $dateHeureDepart = $this->parseDateTime($data['date_heure_depart'] ?? null, $error);
        if ($dateHeureDepart === null) {
            return $this->errorResponse($error ?? 'Invalid date_heure_depart.', Response::HTTP_BAD_REQUEST);
        }

        $dateHeureArrive = $this->parseDateTime($data['date_heure_arrive'] ?? null, $error);
        if ($dateHeureArrive === null) {
            return $this->errorResponse($error ?? 'Invalid date_heure_arrive.', Response::HTTP_BAD_REQUEST);
        }

        // Validation des coordonnées GPS
        $longitudeDepartPassager = $this->parseFloat($data['longitude_point_de_depart_passager'] ?? null, $error);
        if ($longitudeDepartPassager === null) {
            return $this->errorResponse($error ?? 'Invalid longitude_point_de_depart_passager.', Response::HTTP_BAD_REQUEST);
        }

        $latitudeDepartPassager = $this->parseFloat($data['latitude_point_de_depart_passager'] ?? null, $error);
        if ($latitudeDepartPassager === null) {
            return $this->errorResponse($error ?? 'Invalid latitude_point_de_depart_passager.', Response::HTTP_BAD_REQUEST);
        }

        $longitudeArrivePassager = $this->parseFloat($data['longitude_point_arrive_passager'] ?? null, $error);
        if ($longitudeArrivePassager === null) {
            return $this->errorResponse($error ?? 'Invalid longitude_point_arrive_passager.', Response::HTTP_BAD_REQUEST);
        }

        $latitudeArrivePassager = $this->parseFloat($data['latitude_point_arrive_passager'] ?? null, $error);
        if ($latitudeArrivePassager === null) {
            return $this->errorResponse($error ?? 'Invalid latitude_point_arrive_passager.', Response::HTTP_BAD_REQUEST);
        }

        $longitudeRdvPassager = $this->parseFloat($data['longitude_point_de_rdv_passager'] ?? null, $error);
        if ($longitudeRdvPassager === null) {
            return $this->errorResponse($error ?? 'Invalid longitude_point_de_rdv_passager.', Response::HTTP_BAD_REQUEST);
        }

        $latitudeRdvPassager = $this->parseFloat($data['latitude_point_de_rdv_passager'] ?? null, $error);
        if ($latitudeRdvPassager === null) {
            return $this->errorResponse($error ?? 'Invalid latitude_point_de_rdv_passager.', Response::HTTP_BAD_REQUEST);
        }

        // Validation du nombre de passagers
        $nombrePassager = $this->parseInt($data['nombre_de_passager'] ?? null, $error);
        if ($nombrePassager === null || $nombrePassager < 1) {
            return $this->errorResponse($error ?? 'Nombre de passager must be at least 1.', Response::HTTP_BAD_REQUEST);
        }

        // Validation du montant
        $montantTotal = $this->parsePrice($data['montant_total_reservation'] ?? null, true, $error);
        if ($montantTotal === null) {
            return $this->errorResponse($error ?? 'Invalid montant_total_reservation.', Response::HTTP_BAD_REQUEST);
        }

        // Validation du statut
        if (!isset($data['statut_reservation'])) {
            return $this->errorResponse('Statut reservation is required.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $statutReservation = StatutReservation::from($data['statut_reservation']);
        } catch (\ValueError) {
            return $this->errorResponse(
                'Invalid statut_reservation. Accepted values: ' .
                implode(', ', array_map(fn($case) => $case->value, StatutReservation::cases())),
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!isset($data['user_id'])) {
            return $this->errorResponse('User ID is required.', Response::HTTP_BAD_REQUEST);
        }

        $user = $entityManager->find(User::class, $data['user_id']);
        if (!$user) {
            return $this->errorResponse('User not found.', Response::HTTP_BAD_REQUEST);
        }

        // Validation du trajet_id
        // if (!isset($data['trajet_id'])) {
        //     return $this->errorResponse('Trajet ID is required.', Response::HTTP_BAD_REQUEST);
        // }

        // $trajet = $entityManager->getRepository(Trajet::class)->find($data['trajet_id']);
        // if (!$trajet) {
        //     return $this->errorResponse('Trajet not found.', Response::HTTP_BAD_REQUEST);
        // }

        // Création de la réservation
        $reservation = (new Reservation())
            ->setNumeroReservation($numeroReservation)
            ->setDateReservation($dateReservation)
            ->setLongitudePointDeDepartPassager($longitudeDepartPassager)
            ->setLatitudePointDeDepartPassager($latitudeDepartPassager)
            ->setLongitudePointArrivePassager($longitudeArrivePassager)
            ->setLatitudePointArrivePassager($latitudeArrivePassager)
            ->setLongitudePointDeRdvPassager($longitudeRdvPassager)
            ->setLatitudePointDeRdvPassager($latitudeRdvPassager)
            ->setDateHeureDepart($dateHeureDepart)
            ->setDateHeureArrive($dateHeureArrive)
            ->setNombreDePassager($nombrePassager)
            ->setMontantTotalReservation($montantTotal)
            ->setStatutReservation($statutReservation)
            // ->setTrajet($trajet)
            ->setUser($user);

        $entityManager->persist($reservation);
        $entityManager->flush();

        return $this->json($this->serializeReservation($reservation), Response::HTTP_CREATED);
    }

    #[Route('/api/reservations/{id}', name: 'api_reservations_update', methods: ['PUT', 'PATCH'])]
    public function updateReservation(
        int $id,
        Request $request,
        ReservationRepository $repository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $reservation = $repository->find($id);

        if (!$reservation) {
            return $this->errorResponse('Reservation not found.', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        $isPut = $request->getMethod() === 'PUT';

        if (array_key_exists('numero_reservation', $data) || $isPut) {
            $numeroReservation = trim((string) ($data['numero_reservation'] ?? ''));
            if ($numeroReservation === '') {
                return $this->errorResponse('Numero de reservation is required.', Response::HTTP_BAD_REQUEST);
            }
            $reservation->setNumeroReservation($numeroReservation);
        }

        if (array_key_exists('date_reservation', $data) || $isPut) {
            $dateReservation = new \DateTime();
            $reservation->setDateReservation($dateReservation);
        }

        if (array_key_exists('date_heure_depart', $data) || $isPut) {
            $dateHeureDepart = $this->parseDateTime($data['date_heure_depart'] ?? null, $error);
            if ($dateHeureDepart === null) {
                return $this->errorResponse($error ?? 'Invalid date_heure_depart.', Response::HTTP_BAD_REQUEST);
            }
            $reservation->setDateHeureDepart($dateHeureDepart);
        }

        if (array_key_exists('date_heure_arrive', $data) || $isPut) {
            $dateHeureArrive = $this->parseDateTime($data['date_heure_arrive'] ?? null, $error);
            if ($dateHeureArrive === null) {
                return $this->errorResponse($error ?? 'Invalid date_heure_arrive.', Response::HTTP_BAD_REQUEST);
            }
            $reservation->setDateHeureArrive($dateHeureArrive);
        }

        if (array_key_exists('longitude_point_de_depart_passager', $data) || $isPut) {
            $value = $this->parseFloat($data['longitude_point_de_depart_passager'] ?? null, $error);
            if ($value === null) {
                return $this->errorResponse($error ?? 'Invalid longitude_point_de_depart_passager.', Response::HTTP_BAD_REQUEST);
            }
            $reservation->setLongitudePointDeDepartPassager($value);
        }

        if (array_key_exists('latitude_point_de_depart_passager', $data) || $isPut) {
            $value = $this->parseFloat($data['latitude_point_de_depart_passager'] ?? null, $error);
            if ($value === null) {
                return $this->errorResponse($error ?? 'Invalid latitude_point_de_depart_passager.', Response::HTTP_BAD_REQUEST);
            }
            $reservation->setLatitudePointDeDepartPassager($value);
        }

        if (array_key_exists('longitude_point_arrive_passager', $data) || $isPut) {
            $value = $this->parseFloat($data['longitude_point_arrive_passager'] ?? null, $error);
            if ($value === null) {
                return $this->errorResponse($error ?? 'Invalid longitude_point_arrive_passager.', Response::HTTP_BAD_REQUEST);
            }
            $reservation->setLongitudePointArrivePassager($value);
        }

        if (array_key_exists('latitude_point_arrive_passager', $data) || $isPut) {
            $value = $this->parseFloat($data['latitude_point_arrive_passager'] ?? null, $error);
            if ($value === null) {
                return $this->errorResponse($error ?? 'Invalid latitude_point_arrive_passager.', Response::HTTP_BAD_REQUEST);
            }
            $reservation->setLatitudePointArrivePassager($value);
        }

        if (array_key_exists('longitude_point_de_rdv_passager', $data) || $isPut) {
            $value = $this->parseFloat($data['longitude_point_de_rdv_passager'] ?? null, $error);
            if ($value === null) {
                return $this->errorResponse($error ?? 'Invalid longitude_point_de_rdv_passager.', Response::HTTP_BAD_REQUEST);
            }
            $reservation->setLongitudePointDeRdvPassager($value);
        }

        if (array_key_exists('latitude_point_de_rdv_passager', $data) || $isPut) {
            $value = $this->parseFloat($data['latitude_point_de_rdv_passager'] ?? null, $error);
            if ($value === null) {
                return $this->errorResponse($error ?? 'Invalid latitude_point_de_rdv_passager.', Response::HTTP_BAD_REQUEST);
            }
            $reservation->setLatitudePointDeRdvPassager($value);
        }

        if (array_key_exists('nombre_de_passager', $data) || $isPut) {
            $nombrePassager = $this->parseInt($data['nombre_de_passager'] ?? null, $error);
            if ($nombrePassager === null || $nombrePassager < 1) {
                return $this->errorResponse($error ?? 'Nombre de passager must be at least 1.', Response::HTTP_BAD_REQUEST);
            }
            $reservation->setNombreDePassager($nombrePassager);
        }

        if (array_key_exists('montant_total_reservation', $data) || $isPut) {
            $montantTotal = $this->parsePrice($data['montant_total_reservation'] ?? null, true, $error);
            if ($montantTotal === null) {
                return $this->errorResponse($error ?? 'Invalid montant_total_reservation.', Response::HTTP_BAD_REQUEST);
            }
            $reservation->setMontantTotalReservation($montantTotal);
        }

        if (array_key_exists('statut_reservation', $data) || $isPut) {
            $statutReservation = StatutReservation::from($data['statut_reservation']);
            $reservation->setStatutReservation($statutReservation);
        }

        $entityManager->flush();

        return $this->json($this->serializeReservation($reservation));
    }

    #[Route('/api/reservations/{id}', name: 'api_reservations_delete', methods: ['DELETE'])]
    public function deleteReservation(int $id, ReservationRepository $repository, EntityManagerInterface $entityManager): JsonResponse
    {
        $reservation = $repository->find($id);

        if (!$reservation) {
            return $this->errorResponse('Reservation not found.', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($reservation);
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

        $price = (float) $value;
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

    private function parseFloat(mixed $value, ?string &$error): ?float
    {
        if ($value === null || $value === '') {
            $error = 'Float value is required.';
            return null;
        }

        if (!is_numeric($value)) {
            $error = 'Value must be numeric.';
            return null;
        }

        return (float) $value;
    }

    private function parseInt(mixed $value, ?string &$error): ?int
    {
        if ($value === null || $value === '') {
            $error = 'Integer value is required.';
            return null;
        }

        if (!is_numeric($value)) {
            $error = 'Value must be an integer.';
            return null;
        }

        return (int) $value;
    }

    // Fonction permettant de transformer un objet PHP en Structure que JSON peut comprendre (ici un tableau)
    private function serializeReservation(Reservation $reservation): array
    {
        return [
            'id' => $reservation->getId(),
            'numero_reservation' => $reservation->getNumeroReservation(),
            'date_reservation' => $reservation->getDateReservation()->format(\DateTimeInterface::ATOM),
            'longitude_point_de_depart_passager' => $reservation->getLongitudePointDeDepartPassager(),
            'latitude_point_de_depart_passager' => $reservation->getLatitudePointDeDepartPassager(),
            'longitude_point_arrive_passager' => $reservation->getLongitudePointArrivePassager(),
            'latitude_point_arrive_passager' => $reservation->getLatitudePointArrivePassager(),
            'longitude_point_de_rdv_passager' => $reservation->getLongitudePointDeRdvPassager(),
            'latitude_point_de_rdv_passager' => $reservation->getLatitudePointDeRdvPassager(),
            'date_heure_depart' => $reservation->getDateHeureDepart()->format(\DateTimeInterface::ATOM),
            'date_heure_arrive' => $reservation->getDateHeureArrive()->format(\DateTimeInterface::ATOM),
            'nombre_de_passager' => $reservation->getNombreDePassager(),
            'montant_total_reservation' => $reservation->getMontantTotalReservation(),
            'statut_reservation' => $reservation->getStatutReservation()->value,
            'trajet_id' => $reservation->getTrajet()?->getId(),
            'user_id' => $reservation->getUser()?->getId(),
        ];
    }

    // Fonction pour gérer les erreur + status du serveur.
    private function errorResponse(string $message, int $status): JsonResponse
    {
        return $this->json(['error' => $message], $status);
    }
}