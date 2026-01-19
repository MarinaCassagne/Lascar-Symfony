<?php

namespace App\Controller;

use App\Entity\Moderation;  
use App\Repository\ModerationRepository; 
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur API pour la gestion des modérations
 */
class ModerationController extends AbstractController
{     
    /**
     * GET /api/moderations
     * Récupère la liste de toutes les modérations
     */
    #[Route('/api/moderations', name: 'api_moderations_list', methods: ['GET'])]
    public function list(ModerationRepository $repository): JsonResponse
    {
        // Récupération de toutes les entités Moderation
        // Transformation de chaque entité en tableau JSON
        $items = array_map(
            fn (Moderation $item) => $this->serializeModeration($item),
            $repository->findAll()
        );

        // Retourne la liste des modérations au format JSON
        return $this->json($items);
    }

    /**
     * GET /api/moderations/{id}
     * Récupère une modération par son ID
     */
    #[Route('/api/moderations/{id}', name: 'api_moderations_show', methods: ['GET'])]
    public function show(int $id, ModerationRepository $repository): JsonResponse
    {   
        // Recherche de la modération en base de données par son ID
        $item = $repository->find($id);

        // Si la modération n'existe pas → erreur 404
        if (!$item) {
            return $this->errorResponse('Moderation not found.', Response::HTTP_NOT_FOUND);
        }

        // Retourne la modération sérialisée
        return $this->json($this->serializeModeration($item));
    }

    /**
     * POST /api/moderations/add
     * Création d'une nouvelle modération
     */
    #[Route('/api/moderations/add', name: 'api_moderations_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {  
        // Décodage du corps JSON de la requête
        $data = $this->decodeJson($request);

        // Vérification que le JSON est valide
        if ($data === null) {
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        // Validation du champ "motif"
        $motif = $this->parseString($data['motif'] ?? null, true, $motifError);
        if ($motif === null) {
            return $this->errorResponse($motifError ?? 'motif is required.', Response::HTTP_BAD_REQUEST);
        }

        // Validation du canal de modération
        $canal = $this->parseString($data['canal_de_moderation'] ?? null, true, $canalError);
        if ($canal === null) {
            return $this->errorResponse($canalError ?? 'canal_de_moderation is required.', Response::HTTP_BAD_REQUEST);
        }

        // Validation du type de cible
        $typeCible = $this->parseString($data['type_de_cible'] ?? null, true, $typeError);
        if ($typeCible === null) {
            return $this->errorResponse($typeError ?? 'type_de_cible is required.', Response::HTTP_BAD_REQUEST);
        }

        // Validation de l'action de modération
        $action = $this->parseString($data['action_de_moderation'] ?? null, true, $actionError);
        if ($action === null) {
            return $this->errorResponse($actionError ?? 'action_de_moderation is required.', Response::HTTP_BAD_REQUEST);
        }

        // Validation de la date de création
        $dateCreation = $this->parseDateTime($data['date_de_creation'] ?? null, true, $dateError);
        if ($dateCreation === null) {
            return $this->errorResponse($dateError ?? 'date_de_creation is required.', Response::HTTP_BAD_REQUEST);
        }

        // Validation optionnelle de l'ID du trajet
        $trajetId = $this->parseInt($data['id_trajet_id'] ?? null, false, $trajetError);
        if ($trajetError) {
            return $this->errorResponse($trajetError, Response::HTTP_BAD_REQUEST);
        }

        // Validation optionnelle de l'ID de l'avis
        $avisId = $this->parseInt($data['avis_id'] ?? null, false, $avisError);
        if ($avisError) {
            return $this->errorResponse($avisError, Response::HTTP_BAD_REQUEST);
        }

        // Validation optionnelle de l'ID utilisateur
        $userId = $this->parseInt($data['user_id'] ?? null, false, $userError);
        if ($userError) {
            return $this->errorResponse($userError, Response::HTTP_BAD_REQUEST);
        }

        // Création de l'entité Moderation et hydratation des champs
        $item = (new Moderation())
            ->setMotif($motif)
            ->setDateDeCreation($dateCreation)
            ->setCanalDeModeration($canal)
            ->setTypeDeCible($typeCible)
            ->setActionDeModeration($action)
            ->setIdTrajetId($trajetId)
            ->setAvisId($avisId)
            ->setUserId($userId);

        // Sauvegarde en base de données
        $entityManager->persist($item);
        $entityManager->flush();

        // Retourne la modération créée avec le statut HTTP 201
        return $this->json($this->serializeModeration($item), Response::HTTP_CREATED);
    }

    /**
     * PUT / PATCH /api/moderations/{id}
     * Mise à jour complète (PUT) ou partielle (PATCH) d'une modération
     */
    #[Route('/api/moderations/{id}', name: 'api_moderations_update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        ModerationRepository $repository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Recherche de la modération existante
        $item = $repository->find($id);

        // Si inexistante → 404
        if (!$item) {
            return $this->errorResponse('Moderation not found.', Response::HTTP_NOT_FOUND);
        }

        // Décodage du JSON
        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        // Détection du type de requête (PUT ou PATCH)
        $isPut = $request->getMethod() === 'PUT';

        // Mise à jour conditionnelle de chaque champ
        if (array_key_exists('motif', $data) || $isPut) {
            $motif = $this->parseString($data['motif'] ?? null, true, $motifError);
            if ($motif === null) return $this->errorResponse($motifError ?? 'motif is required.', Response::HTTP_BAD_REQUEST);
            $item->setMotif($motif);
        }

        if (array_key_exists('date_de_creation', $data) || $isPut) {
            $dateCreation = $this->parseDateTime($data['date_de_creation'] ?? null, true, $dateError);
            if ($dateCreation === null) return $this->errorResponse($dateError ?? 'date_de_creation is required.', Response::HTTP_BAD_REQUEST);
            $item->setDateDeCreation($dateCreation);
        }

        if (array_key_exists('canal_de_moderation', $data) || $isPut) {
            $canal = $this->parseString($data['canal_de_moderation'] ?? null, true, $canalError);
            if ($canal === null) return $this->errorResponse($canalError ?? 'canal_de_moderation is required.', Response::HTTP_BAD_REQUEST);
            $item->setCanalDeModeration($canal);
        }

        if (array_key_exists('type_de_cible', $data) || $isPut) {
            $typeCible = $this->parseString($data['type_de_cible'] ?? null, true, $typeError);
            if ($typeCible === null) return $this->errorResponse($typeError ?? 'type_de_cible is required.', Response::HTTP_BAD_REQUEST);
            $item->setTypeDeCible($typeCible);
        }

        if (array_key_exists('action_de_moderation', $data) || $isPut) {
            $action = $this->parseString($data['action_de_moderation'] ?? null, true, $actionError);
            if ($action === null) return $this->errorResponse($actionError ?? 'action_de_moderation is required.', Response::HTTP_BAD_REQUEST);
            $item->setActionDeModeration($action);
        }

        if (array_key_exists('id_trajet_id', $data) || $isPut) {
            $trajetId = $this->parseInt($data['id_trajet_id'] ?? null, $isPut, $trajetError);
            if ($trajetId === null && $isPut) return $this->errorResponse($trajetError ?? 'id_trajet_id is required.', Response::HTTP_BAD_REQUEST);
            $item->setIdTrajetId($trajetId);
        }

        if (array_key_exists('avis_id', $data) || $isPut) {
            $avisId = $this->parseInt($data['avis_id'] ?? null, $isPut, $avisError);
            if ($avisId === null && $isPut) return $this->errorResponse($avisError ?? 'avis_id is required.', Response::HTTP_BAD_REQUEST);
            $item->setAvisId($avisId);
        }

        if (array_key_exists('user_id', $data) || $isPut) {
            $userId = $this->parseInt($data['user_id'] ?? null, $isPut, $userError);
            if ($userId === null && $isPut) return $this->errorResponse($userError ?? 'user_id is required.', Response::HTTP_BAD_REQUEST);
            $item->setUserId($userId);
        }

        // Enregistrement des modifications
        $entityManager->flush();

        // Retourne la modération mise à jour
        return $this->json($this->serializeModeration($item));
    }

    // -----------------------------
    // Helpers
    // -----------------------------

    /**
     * Transforme une entité Moderation en tableau prêt pour le JSON
     */
    private function serializeModeration(Moderation $item): array
    {
        return [
            'id' => method_exists($item, 'getId') ? $item->getId() : null,
            'motif' => $item->getMotif(),
            'date_de_creation' => $item->getDateDeCreation()?->format(\DateTimeInterface::ATOM),
            'canal_de_moderation' => $item->getCanalDeModeration(),
            'type_de_cible' => $item->getTypeDeCible(),
            'action_de_moderation' => $item->getActionDeModeration(),
            'id_trajet_id' => $item->getIdTrajetId(),
            'avis_id' => $item->getAvisId(),
            'user_id' => $item->getUserId(),
        ];
    }

    /**
     * Décode le JSON de la requête HTTP
     */
    private function decodeJson(Request $request): ?array
    {
        $raw = $request->getContent();
        if ($raw === '') return null;

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Génère une réponse d'erreur JSON standardisée
     */
    private function errorResponse(string $message, int $status): JsonResponse
    {
        return $this->json(['error' => $message], $status);
    }

    /**
     * Valide et convertit une valeur en string
     */
    private function parseString(mixed $value, bool $required, ?string &$error = null): ?string
    {
        if ($value === null || $value === '') {
            if ($required) $error = 'Value is required.';
            return null;
        }
        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }
        $error = 'Value must be a string.';
        return null;
    }

    /**
     * Valide et convertit une date en DateTimeImmutable
     */
    private function parseDateTime(mixed $value, bool $required, ?string &$error = null): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            if ($required) $error = 'Value is required.';
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        if (!is_string($value)) {
            $error = 'Value must be a datetime string.';
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            $error = 'Invalid datetime format (expected ISO string like 2026-01-19T10:30:00+01:00).';
            return null;
        }
    }

    /**
     * Valide et convertit une valeur en entier
     */
    private function parseInt(mixed $value, bool $required, ?string &$error = null): ?int
    {
        if ($value === null || $value === '') {
            if ($required) $error = 'Value is required.';
            return null;
        }
        if (is_int($value)) return $value;
        if (is_numeric($value)) return (int) $value;

        $error = 'Value must be an integer.';
        return null;
    }
}
