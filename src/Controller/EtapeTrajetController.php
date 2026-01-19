<?php

namespace App\Controller;

use App\Entity\EtapeTrajet;
use App\Repository\EtapeTrajetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/etape-trajets')]
final class EtapeTrajetController extends AbstractController
{
    #[Route('', name: 'api_etape_trajet_index', methods: ['GET'])]
    public function index(EtapeTrajetRepository $repo): JsonResponse
    {
        $items = $repo->findAll();

        // Si tu as le Serializer + groupes, tu peux renvoyer directement $items
        // return $this->json($items, 200, [], ['groups' => ['etape_trajet:read']]);

        return $this->json(array_map(fn(EtapeTrajet $e) => $this->toDto($e), $items));
    }

    #[Route('/{id}', name: 'api_etape_trajet_show', methods: ['GET'])]
    public function show(EtapeTrajet $etapeTrajet): JsonResponse
    {
        return $this->json($this->toDto($etapeTrajet));
    }

    #[Route('', name: 'api_etape_trajet_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->toArray();

        $etape = new EtapeTrajet();

        // TODO: mappe tes champs réels ici
        // $etape->setNom($data['nom'] ?? null);
        // $etape->setOrdre((int)($data['ordre'] ?? 0));
        // ...

        $em->persist($etape);
        $em->flush();

        return $this->json($this->toDto($etape), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_etape_trajet_update', methods: ['PUT'])]
    public function update(Request $request, EtapeTrajet $etapeTrajet, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->toArray();

        // TODO: mappe tes champs réels ici (PUT = remplace, donc souvent on exige tout)
        // $etapeTrajet->setNom($data['nom']);
        // $etapeTrajet->setOrdre((int)$data['ordre']);
        // ...

        $em->flush();

        return $this->json($this->toDto($etapeTrajet));
    }

    #[Route('/{id}', name: 'api_etape_trajet_patch', methods: ['PATCH'])]
    public function patch(Request $request, EtapeTrajet $etapeTrajet, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->toArray();

        // PATCH = partiel : tu ne set que si présent
        // if (array_key_exists('nom', $data)) $etapeTrajet->setNom($data['nom']);
        // if (array_key_exists('ordre', $data)) $etapeTrajet->setOrdre((int)$data['ordre']);
        // ...

        $em->flush();

        return $this->json($this->toDto($etapeTrajet));
    }

    #[Route('/{id}', name: 'api_etape_trajet_delete', methods: ['DELETE'])]
    public function delete(EtapeTrajet $etapeTrajet, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($etapeTrajet);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function toDto(EtapeTrajet $e): array
    {
        return [
            'id' => $e->getId(),
            // TODO: expose tes champs
            // 'nom' => $e->getNom(),
            // 'ordre' => $e->getOrdre(),
        ];
    }
}
