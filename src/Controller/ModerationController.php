<?php

namespace App\Controller;
// c'était App\Controller\Api donc ça marchait pas :(

use App\Entity\Moderation;
use App\Repository\ModerationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/moderations')]
final class ModerationController extends AbstractController
{
    #[Route('', name: 'api_moderation_index', methods: ['GET'])]
    public function index(ModerationRepository $repo): JsonResponse
    {
        $moderations = $repo->findAll();

        // Idéalement: Serializer / Groups. Ici: réponse simple "à la main".
        $data = array_map(fn (Moderation $m) => $this->toArray($m), $moderations);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('', name: 'api_moderation_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $moderation = new Moderation();

        // TODO: adapte selon TES champs (ex: status, reason, contentId, userId, etc.)
        // Exemple :
        // $moderation->setStatus($payload['status'] ?? null);

        // Validation minimale
        // if (null === $moderation->getStatus()) {
        //     return $this->json(['error' => 'status is required'], Response::HTTP_UNPROCESSABLE_ENTITY);
        // }

        $em->persist($moderation);
        $em->flush();

        return $this->json($this->toArray($moderation), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_moderation_show', methods: ['GET'])]
    public function show(Moderation $moderation): JsonResponse
    {
        return $this->json($this->toArray($moderation), Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_moderation_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Moderation $moderation, EntityManagerInterface $em): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        // PUT/PATCH : tu mets à jour uniquement les champs présents
        // TODO: adapte selon TES champs
        // if (array_key_exists('status', $payload)) {
        //     $moderation->setStatus($payload['status']);
        // }

        $em->flush();

        return $this->json($this->toArray($moderation), Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_moderation_delete', methods: ['DELETE'])]
    public function delete(Moderation $moderation, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($moderation);
        $em->flush();

        // 204: no content (classique REST)
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function toArray(Moderation $m): array
    {
        // TODO: adapte selon tes getters réels
        return [
            'id' => $m->getId(),
            // 'status' => $m->getStatus(),
            // 'reason' => $m->getReason(),
            // 'createdAt' => $m->getCreatedAt()?->format(DATE_ATOM),
        ];
    }
}
