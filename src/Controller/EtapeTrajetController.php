<?php

namespace App\Controller;

use App\Entity\EtapeTrajet;
use App\Entity\Trajet;
use App\Repository\EtapeTrajetRepository;
use App\Repository\TrajetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EtapeTrajetController extends AbstractController
{
    #[Route('/api/etape-trajets', name: 'api_etape_tragets_list', methods: ['GET'])]
    public function list(EtapeTrajetRepository $repository): JsonResponse
    {
        $items = array_map(
            fn (EtapeTrajet $item) => $this->serializeEtapeTraget($item),
            $repository->findAll()
        );

        return $this->json($items);
    }

    #[Route('/api/etape-tragets/{id}', name: 'api_etape_tragets_show', methods: ['GET'])]
    public function show(int $id, EtapeTrajetRepository $repository): JsonResponse
    {
        $item = $repository->find($id);

        if (!$item) {
            return $this->errorResponse('EtapeTraget not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeEtapeTraget($item));
    }

    #[Route('/api/etapeTrajet/add', name: 'api_etape_trajets_create', methods: ['POST'])]
    //function create
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        TrajetRepository $trajetRepository
    ): JsonResponse {
        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        $lat = $this->parseFloat($data['latitude_etape'] ?? null, true, $latError);
        if ($lat === null) {
            return $this->errorResponse($latError ?? 'Invalid latitude_etape.', Response::HTTP_BAD_REQUEST);
        }

        $lng = $this->parseFloat($data['longitude_etape'] ?? null, true, $lngError);
        if ($lng === null) {
            return $this->errorResponse($lngError ?? 'Invalid longitude_etape.', Response::HTTP_BAD_REQUEST);
        }

        // $trajet = $entityManager->find(Trajet::class,$data["trajet_id"]);
        // if (!$trajet) {
        //     return $this->errorResponse('Trajet not found.', Response::HTTP_BAD_REQUEST);
        // }

        $item = (new EtapeTrajet())
            ->setLatitudeEtape($lat)
            ->setLongitudeEtape($lng);
            // ->setTrajet($trajet);

        $entityManager->persist($item);
        $entityManager->flush();

        return $this->json($this->serializeEtapeTraget($item), Response::HTTP_CREATED);
    }

    #[Route('/api/etape-tragets/{id}', name: 'api_etape_tragets_update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        EtapeTrajetRepository $repository,
        EntityManagerInterface $entityManager,
        TrajetRepository $trajetRepository
    ): JsonResponse {
        $item = $repository->find($id);

        if (!$item) {
            return $this->errorResponse('EtapeTraget not found.', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        $isPut = $request->getMethod() === 'PUT';

        if (array_key_exists('latitude_etape', $data) || $isPut) {
            $lat = $this->parseFloat($data['latitude_etape'] ?? null, true, $latError);
            if ($lat === null) {
                return $this->errorResponse($latError ?? 'latitude_etape is required.', Response::HTTP_BAD_REQUEST);
            }
            $item->setLatitudeEtape($lat);
        }

        if (array_key_exists('longitude_etape', $data) || $isPut) {
            $lng = $this->parseFloat($data['longitude_etape'] ?? null, true, $lngError);
            if ($lng === null) {
                return $this->errorResponse($lngError ?? 'longitude_etape is required.', Response::HTTP_BAD_REQUEST);
            }
            $item->setLongitudeEtape($lng);
        }

        if (array_key_exists('trajet_id', $data) || $isPut) {
            $trajetId = $this->parseInt($data['trajet_id'] ?? null, true, $trajetError);
            if ($trajetId === null) {
                return $this->errorResponse($trajetError ?? 'trajet_id is required.', Response::HTTP_BAD_REQUEST);
            }

            $trajet = $trajetRepository->find($trajetId);
            if (!$trajet) {
                return $this->errorResponse('Trajet not found.', Response::HTTP_BAD_REQUEST);
            }

            $item->setTrajet($trajet);
        }

        $entityManager->flush();

        return $this->json($this->serializeEtapeTraget($item));
    }

    // -----------------------------
    // Helpers (minimal + safe)
    // -----------------------------

    private function serializeEtapeTraget(EtapeTrajet $item): array
    {
        return [
            'id' => method_exists($item, 'getId') ? $item->getId() : null,
            'latitude_etape' => $item->getLatitudeEtape(),
            'longitude_etape' => $item->getLongitudeEtape(),
            'trajet_id' => $item->getTrajet() ? $item->getTrajet()->getId() : null,
        ];
    }

    private function decodeJson(Request $request): ?array
    {
        $raw = $request->getContent();
        if ($raw === '') {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return $this->json(['error' => $message], $status);
    }
//conditions
    private function parseFloat(mixed $value, bool $required, ?string &$error = null): ?float
    {
        if ($value === null || $value === '') {
            if ($required) $error = 'Value is required.';
            return $required ? null : null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $error = 'Value must be a number.';
        return null;
    }

    private function parseInt(mixed $value, bool $required, ?string &$error = null): ?int
    {
        if ($value === null || $value === '') {
            if ($required) $error = 'Value is required.';
            return $required ? null : null;
        }
        if (is_numeric($value) && (string)(int)$value === (string)$value || is_int($value)) {
            return (int) $value;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $error = 'Value must be an integer.';
        return null;
    }
}
