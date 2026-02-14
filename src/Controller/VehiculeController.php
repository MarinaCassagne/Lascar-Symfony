<?php

namespace App\Controller;

use App\Entity\Vehicule;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehiculeController extends AbstractController
{
    #[Route('/api/vehicules', name: 'api_vehicules_list', methods: ['GET'])]
    public function listeVehicules(VehiculeRepository $repository): JsonResponse
    {
        $vehicules = array_map(
            fn(Vehicule $vehicule) => $this->serializeVehicule($vehicule),
            $repository->findAll()
        );

        return $this->json($vehicules);
    }

    #[Route('/api/vehicules/{id}', name: 'api_vehicules_show', methods: ['GET'])]
    public function show(int $id, VehiculeRepository $repository): JsonResponse
    {
        $vehicule = $repository->find($id);

        if (!$vehicule) {
            return $this->errorResponse('Vehicule not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeVehicule($vehicule));
    }

    #[Route('/api/ajout_vehicule', name: 'api_vehicules_create', methods: ['POST'])]
    public function ajouterVehicule(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        $marque = trim((string) ($data['marque'] ?? ''));
        if ($marque === '') {
            return $this->errorResponse('Marque is required.', Response::HTTP_BAD_REQUEST);
        }

        $modele = trim((string) ($data['modele'] ?? ''));
        if ($modele === '') {
            return $this->errorResponse('Modele is required.', Response::HTTP_BAD_REQUEST);
        }

        $couleur = trim((string) ($data['couleur'] ?? ''));
        if ($couleur === '') {
            return $this->errorResponse('Couleur is required.', Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();

        if (!$user) {
            return $this->errorResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);
        }

        $vehicule = (new Vehicule())
            ->setMarque($marque)
            ->setModele($modele)
            ->setCouleur($couleur)
            ->setUser($user);

        $entityManager->persist($vehicule);
        $entityManager->flush();

        return $this->json($this->serializeVehicule($vehicule), Response::HTTP_CREATED);
    }

    #[Route('/api/vehicules/{id}', name: 'api_vehicules_update', methods: ['PUT', 'PATCH'])]
    public function updateVehicule(int $id, Request $request, VehiculeRepository $repository, EntityManagerInterface $entityManager): JsonResponse
    {
        $vehicule = $repository->find($id);

        if (!$vehicule) {
            return $this->errorResponse('Vehicule not found.', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        $isPut = $request->getMethod() === 'PUT';

        if (array_key_exists('marque', $data) || $isPut) {
            $marque = trim((string) ($data['marque'] ?? ''));
            if ($marque === '') {
                return $this->errorResponse('Marque is required.', Response::HTTP_BAD_REQUEST);
            }
            $vehicule->setMarque($marque);
        }

        if (array_key_exists('modele', $data) || $isPut) {
            $modele = trim((string) ($data['modele'] ?? ''));
            if ($modele === '') {
                return $this->errorResponse('Modele is required.', Response::HTTP_BAD_REQUEST);
            }
            $vehicule->setModele($modele);
        }

        if (array_key_exists('couleur', $data) || $isPut) {
            $couleur = trim((string) ($data['couleur'] ?? ''));
            if ($couleur === '') {
                return $this->errorResponse('Couleur is required.', Response::HTTP_BAD_REQUEST);
            }
            $vehicule->setCouleur($couleur);
        }

        $entityManager->flush();

        return $this->json($this->serializeVehicule($vehicule));
    }

    #[Route('/api/vehicules/{id}', name: 'api_vehicules_delete', methods: ['DELETE'])]
    public function delete(int $id, VehiculeRepository $repository, EntityManagerInterface $entityManager): JsonResponse
    {
        $vehicule = $repository->find($id);

        if (!$vehicule) {
            return $this->errorResponse('Vehicule not found.', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($vehicule);
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

    private function serializeVehicule(Vehicule $vehicule): array
    {
        return [
            'id' => $vehicule->getId(),
            'marque' => $vehicule->getMarque(),
            'modele' => $vehicule->getModele(),
            'couleur' => $vehicule->getCouleur(),
            'user' => [
                'id' => $vehicule->getUser()->getId(),
                'nom' => $vehicule->getUser()->getNom(),
                'prenom' => $vehicule->getUser()->getPrenom(),
            ],
        ];
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return $this->json(['error' => $message], $status);
    }
}
