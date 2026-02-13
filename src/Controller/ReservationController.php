<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\Trajet;
use App\Enum\StatutReservation;
use App\Repository\ReservationRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\OpenStreetMapService;

class ReservationController extends AbstractController
{
    // Fonction pour lister toutes les réservations 
    #[Route('/api/reservations', name: 'api_reservations_list', methods: ['GET'])]
    public function listeReservations(ReservationRepository $repository): JsonResponse
    {
        // Créer un tableau php en récupérant chaque élément Réservations dans ma base de données avec la méthode findAll
        // et les mets au format voulu avec la fonction serializeReservation créé plus bas
        $reservations = array_map(
            fn(Reservation $reservation) => $this->serializeReservation($reservation),
            $repository->findAll()
        );

        // Transforme le tableau PHP en tableau JSON
        return $this->json($reservations);
    }


    // Fonction pour afficher une réservations en fonction de l'ID
    #[Route('/api/reservations/{id}', name: 'api_reservations_show', methods: ['GET'])]
    public function findReservationByID(int $id, ReservationRepository $repository): JsonResponse
    {
        // Stockage de la réservation dans une variable
        // Méthode Find()=> permet de trouver une instance grâce à la Primary Key (ici ID)
        $reservation = $repository->find($id);

        // Vérification qu'il me renvoie bien une réservation sinon message d'erreur
        if (!$reservation) {
            return $this->errorResponse('Reservation not found.', Response::HTTP_NOT_FOUND);
        }

        // Si Reservation trouvé renvoie la réservation au format gérer avec la fonction serializeReservation crée plus bas.
        return $this->json($this->serializeReservation($reservation));
    }


    // Fonction pour créer une réservation
    #[Route('/api/trajets/{id}/reserver', name: 'api_reservations_create', methods: ['POST'])]
    public function reserver(Trajet $trajet,Request $request, EntityManagerInterface $entityManager, OpenStreetMapService $osm): JsonResponse
    {
        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();

        if (!$user) {
            return $this->errorResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);
        }

        $id_User = $user->getId();

        $id_Trajet = $trajet->getId();
        $random = rand(0, 100);
        // Validation du numéro de réservation
        $numeroReservation = $id_User . '-' . $id_Trajet . '-' . $random;

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
        $lieu_depart = $data['lieu_de_depart'];

        if ($lieu_depart === '' || $lieu_depart === null) {
            return $this->errorResponse('Departure place is required.', Response::HTTP_BAD_REQUEST);
        }

        // Je récupère les coordonnées du lieu de départ
        $coordDepart = $osm->geocode($lieu_depart);

        $lieu_arrivee = $data['lieu_arrivee'];

        if ($lieu_arrivee === '' || $lieu_arrivee === null) {
            return $this->errorResponse('Departure place is required.', Response::HTTP_BAD_REQUEST);
        }

        // Je récupère les coordonnées du lieu de départ
        $coordArrivee = $osm->geocode($lieu_arrivee);

        $longitudeDepartPassager = $coordDepart['lon'];

        $latitudeDepartPassager = $coordDepart['lat'];

        $longitudeArrivePassager = $coordArrivee['lon'];

        $latitudeArrivePassager = $coordArrivee['lat'];

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

        // Validation du trajet_id

        // Création de la réservation
        $reservation = (new Reservation())
            ->setNumeroReservation($numeroReservation)
            ->setDateReservation($dateReservation)
            ->setLieuDepartPassager($lieu_depart)
            ->setLieuArriveePassager($lieu_arrivee)
            ->setLongitudePointDeDepartPassager($longitudeDepartPassager)
            ->setLatitudePointDeDepartPassager($latitudeDepartPassager)
            ->setLongitudePointArrivePassager($longitudeArrivePassager)
            ->setLatitudePointArrivePassager($latitudeArrivePassager)
            ->setDateHeureDepart($dateHeureDepart)
            ->setDateHeureArrive($dateHeureArrive)
            ->setNombreDePassager($nombrePassager)
            ->setMontantTotalReservation($montantTotal)
            ->setStatutReservation($statutReservation)
            ->setTrajet($trajet)
            ->setUser($user);

        $entityManager->persist($reservation);
        $entityManager->flush();

        return $this->json($this->serializeReservation($reservation), Response::HTTP_CREATED);
    }

    #[Route('/api/reservations/{id}', name: 'api_reservations_update', methods: ['PUT', 'PATCH'])]
    public function update(
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
            $statutReservation = $this->parseStatut($data['statut_reservation'] ?? null, $error);
            if ($statutReservation === null) {
                return $this->errorResponse($error ?? 'Invalid statut_reservation.', Response::HTTP_BAD_REQUEST);
            }
            $reservation->setStatutReservation($statutReservation);
        }

        $entityManager->flush();

        return $this->json($this->serializeReservation($reservation));
    }

    #[Route('/api/reservations/{id}', name: 'api_reservations_delete', methods: ['DELETE'])]
    public function delete(int $id, ReservationRepository $repository, EntityManagerInterface $entityManager): JsonResponse
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

    private function serializeReservation(Reservation $reservation): array
    {
        return [
            'id' => $reservation->getId(),
            'numero_reservation' => $reservation->getNumeroReservation(),
            'date_reservation' => $reservation->getDateReservation()->format(\DateTimeInterface::ATOM),
            'lieu_depart' => $reservation->getLieuDepartPassager(),
            'lieu_arrivee' => $reservation->getLieuArriveePassager(),
            'longitude_point_de_depart_passager' => $reservation->getLongitudePointDeDepartPassager(),
            'latitude_point_de_depart_passager' => $reservation->getLatitudePointDeDepartPassager(),
            'longitude_point_arrive_passager' => $reservation->getLongitudePointArrivePassager(),
            'latitude_point_arrive_passager' => $reservation->getLatitudePointArrivePassager(),
            'date_heure_depart' => $reservation->getDateHeureDepart()->format(\DateTimeInterface::ATOM),
            'date_heure_arrive' => $reservation->getDateHeureArrive()->format(\DateTimeInterface::ATOM),
            'nombre_de_passager' => $reservation->getNombreDePassager(),
            'montant_total_reservation' => $reservation->getMontantTotalReservation(),
            'statut_reservation' => $reservation->getStatutReservation()->value,
            'trajet_id' => $reservation->getTrajet()?->getId(),
            'user_id' => $reservation->getUser()?->getId(),
        ];
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return $this->json(['error' => $message], $status);
    }
}