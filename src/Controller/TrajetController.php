<?php

namespace App\Controller;

use App\Entity\EtapeTrajet;
use App\Entity\Trajet; // Entité Trajet pour pouvoir instancier un trajet
use App\Entity\User; // Entité User pour pouvoir instancier récupérer IdUser
use App\Entity\Moderation; // Entité Moderation pour pouvoir instancier récupérer IdModeration
use App\Entity\Reservation;
use App\Form\TrajetType;
use App\Enum\NatureTrajet;
use App\Enum\TypeTrajet;
use App\Enum\StatutValidTrajet;
use App\Repository\TrajetRepository;
use App\Service\OpenStreetMapService;
use Doctrine\ORM\EntityManagerInterface; // pour sauvergarder ou supprimer dans la BDD
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // classe parente pour utiliser des méthodes spécifique comme render() pour renvoyer une vue ou json() pour renvoyer un tableau en JSON
use Symfony\Component\HttpFoundation\JsonResponse; // pour renvoyer une réponse au format JSON
use Symfony\Component\HttpFoundation\Request; // pour utiliser les methodes HTTP: GET, POST, DELETE, UPDATE... 
use Symfony\Component\HttpFoundation\Response; // pour renvoyer les codes HTTP (202, 404, ...)
use Symfony\Component\Routing\Attribute\Route; // pour définir des URLs
use Symfony\Component\Validator\Validator\ValidatorInterface; //

final class TrajetController extends AbstractController
{
    // URL pour avoir la liste des trajets
    #[Route('/api/trajets', name: 'app_trajets_list', methods: ['GET'])]

    // ========================================================================
    //                        ✨LISTER TOUS LES TRAJETS
    // ========================================================================

    // Lister les trajets sous format JSON
    public function listTrajets(Request $request, TrajetRepository $trajetRepository): JsonResponse
    {
        $limit = $request->query->get('limit');

        if($limit){
            $trajets =array_map(
                fn(Trajet $trajet) => $this->serializeTrajet($trajet),
                $trajetRepository->findBy([], null, (int) $limit)
            );
        }else{
            // Permet de mettre dans un tableau le résulat de la fonction 
            $trajets = array_map(

                fn(Trajet $trajet) => $this->serializeTrajet($trajet),

                // Récupère tous les trajets de la BDD
                $trajetRepository->findAll()
            );   
        }

        // Renvoie un tableau des trajets en format JSON avec le code HTTP 200, si réponse serveur (OK)
        return $this->json($trajets);
    }

    // ========================================================================
    //                        ✨CRÉER UN TRAJET
    // ========================================================================

    #[Route('/api/publier_trajet', name: 'app_trajet_create', methods: ['POST'])]

    // Créer un nouveau trajet et le sauvegarder en base de données
    public function publierTrajet(Request $request, EntityManagerInterface $entityManager, OpenStreetMapService $osm): JsonResponse
    {
        // Décode le JSON envoyé dans le corps de la requête
        // Transforme {"date_de_depart":2026-09-23 08:25:34,"prix":3,40} en tableau associatif PHP ['date_de_depart'=>'2026-09-23 08:25:34','prix'=>3,40]
        $data = $this->decodeJson($request);

        if ($data === null) {
            // Si le JSON est invalide, retourner une erreur avec le code HTTP (HperTexte Transfer Protocol) 400.
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        // ==============================================
        //         VÉRIFICATION / VALIDATION 
        // ==============================================v

        //============= "ADRESSES" =====================

        // TODO ⚠️ AJOUTER LA VÉRIFICATION : SI LES ADRESSES EXISTENT

        //=== ADRESSE LIEU DE DÉPART================

        $lieu_depart = $data['lieu_de_depart'];

        if ($lieu_depart === '' || $lieu_depart === null) {
            return $this->errorResponse('Departure place is required.', Response::HTTP_BAD_REQUEST);
        }

        // Je récupère les coordonnées du lieu de départ
        $coordDepart = $osm->geocode($lieu_depart);



        $lieu_arrivee = $data['lieu_arrivee'];

        if ($lieu_arrivee === '' || $lieu_arrivee === null) {
            return $this->errorResponse('Arrival place is required.', Response::HTTP_BAD_REQUEST);
        }

        // Je récupère les coordonnées du lieu d'arrivé
        $coordArrivee = $osm->geocode($lieu_arrivee);

        //============= DATE DE DÉPART ==================

        // Récupérer la donnée date de départ dans la requête qui sera au format string après decodeJson
        $date_de_depart = $data['date_de_depart'] ?? '';

        // Valider le format de date

        // Nettoyer et valider la data $date_de_depart

        // Si la date de départ n'est pas renseignée, 
        if ($date_de_depart === '' || $date_de_depart === null) {
            // Alors retourner une erreur avec le code HTTP (HperTexte Transfer Protocol) 400.
            return $this->errorResponse('Departure date is required.', Response::HTTP_BAD_REQUEST);
        }

        $date_depart = new \DateTime($date_de_depart);

        // TODO faire vérif serveur pour les Coordonnées, et les données distance et durée


        // Récupérer la donnée longitude_lieu_depart_conducteur dans la requête
        $longitude_lieu_depart_conducteur = $coordDepart['lon'];


        // Récupérer la donnée latitude_lieu_depart_conducteur dans la requête
        $latitude_lieu_depart_conducteur = $coordDepart['lat'];


        //=== ADRESSE LIEU DE D'ARRIVÉE ============

        // Récupérer la donnée longitude_lieu_arrive_conducteur dans la requête
        $longitude_lieu_arrive_conducteur = $coordArrivee['lon'];

        // Récupérer la donnée latitude_lieu_arrive_conducteur dans la requête
        $latitude_lieu_arrive_conducteur = $coordArrivee['lat'];

        // Tableau contenant mes différents points du trajets
        $points = [
            $coordDepart,
            $coordArrivee
        ];

        $route = $osm->donneesTrajet($points);

        // Récupérer la donnée durée dans la requête
        $duree = $route['durationMin'];


        // Récupérer la donnée nombre de km dans la requête
        $nombre_de_km = $route['distanceKm'];


        //============= NOMBRE DE PLACES ===============

        // Récupérer la donnée nombre de place dans la requête
        $nombre_de_place = $data['nombre_de_place'] ?? '';

        // Nettoyer et valider la donnée nombre de place
        if (!is_integer($nombre_de_place) & $nombre_de_place === '') {
            // Si le nombre de place n'est pas un nombre et non renseigné, retourner une erreur avec le code HTTP (HperTexte Transfer Protocol) 400.
            return $this->errorResponse('Number of places is required.', Response::HTTP_BAD_REQUEST);
        }

        //============= PRIX ===========================

        // Récupérer la donnée prix, la nettoyer
        $prix = $this->parseFloat($data, $data['prix'] ?? null, true, $error);

        // Valider la donnée prix
        if ($prix === null) {
            // Si le prix est null, retourner une erreur avec le code HTTP (HperTexte Transfer Protocol) 400.
            return $this->errorResponse($error ?? 'Invalid price.', Response::HTTP_BAD_REQUEST);
        }

        //======== NATURE DU TRAJET ==================

        // Récupérer la nature du trajet dans la requête (OFFRE, DEMANDE)
        $nature_trajet = $data['nature_trajet'] ?? '';
        // Si la nature du trajet n'est pas égale à la liste des enum, 
        // TODO https://symfony.com/doc/current/ai/components/agent.html#automatic-enum-validation

        // Si la nature du trajet n'est pas renseigné
        if ($nature_trajet === '') {
            // alors retourner une erreur avec le code HTTP (HperTexte Transfer Protocol) 400.
            return $this->errorResponse('Nature of the journey is required.', Response::HTTP_BAD_REQUEST);
        }


        try {
            $nature_trajet = NatureTrajet::from($nature_trajet);
        } catch (\ValueError $e) {
            return $this->errorResponse(
                'Nature of the journey must be Offre or Demande.',
                Response::HTTP_BAD_REQUEST
            );
        }

        //======== TYPE DE TRAJET ====================

        // Récupérer le type de trajet dans la requête (DOMICILE_TRAVAIL, EVENEMENT)
        $type_trajet = $data['type_trajet'] ?? '';

        // Si le type du trajet n'est pas égale à un élément de l'enum, 
        // TODO https://symfony.com/doc/current/ai/components/agent.html#automatic-enum-validation

        // Si le type du trajet n'est pas renseigné,
        if ($type_trajet === '') {
            // Alors retourner une erreur avec le code HTTP (HperTexte Transfer Protocol) 400.
            return $this->errorResponse('Type of journey is invalid (DOMICILE_TRAVAIL, EVENEMENT).', Response::HTTP_BAD_REQUEST);
        }

        try {
            $type_trajet = TypeTrajet::from($type_trajet);
        } catch (\ValueError $e) {
            return $this->errorResponse(
                'Type of the journey must be "Domicile Travail" or "Évènement" .',
                Response::HTTP_BAD_REQUEST
            );
        }


        //======== DATE DE PUBLICATION ================


        // TODO AJOUTER LA VÉRIFICATION DU FORMAT DE LA DATE : Utiliser DateTimeValidator ?
        // Nettoyer et valider la data $date_de_publication

        $date_publication = new \DateTime();


        //======== ID USER ========

        $user = $this->getUser();

        if (!$user) {
            return $this->errorResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);
        }

        // //======== ID ÉTAPE TRAJET ========

        // // Récupérer l'idEtapeTrajet dans la requête associé à l'i
        // $idEtapeTrajet = $entityManager->getRepository(EtapeTrajet::class)->find($data['idEtateTrajet']);

        // // Renvoyer une message si idEtapeTrajet non trouvé avec le code HTTP (HperTexte Transfer Protocol) 400.
        // if (!$idEtapeTrajet) {
        //     return $this->errorResponse('Journey stage id : {idEtapeTrajet} not found.', Response::HTTP_BAD_REQUEST);
        // }

        // ==============================================
        //         CRÉATION D'UN TRAJET 
        // ==============================================    

        // Instancier l'objet trajet
        $trajet = (new Trajet())
            ->setDateDeDepart($date_depart)
            ->setLieuDepartConducteur($lieu_depart)
            ->setLieuArriveeConducteur($lieu_arrivee)
            ->setLongitudeLieuDepartConducteur($longitude_lieu_depart_conducteur)
            ->setLatitudeLieuDepartConducteur($latitude_lieu_depart_conducteur)
            ->setLongitudeLieuArriveConducteur($longitude_lieu_arrive_conducteur)
            ->setLatitudeLieuArriveConducteur($latitude_lieu_arrive_conducteur)
            ->setDuree($duree)
            ->setNombreDeKm($nombre_de_km)
            ->setNombreDePlace($nombre_de_place)
            ->setPrix($prix)
            ->setNatureTrajet($nature_trajet)
            ->setTypeTrajet($type_trajet)
            ->setDateDePublication($date_publication)
            ->setUser($user);
        // ->addIdEtapeTrajet($idEtapeTrajet)

        // Sauvegarder le trajet dans la BDD

        $entityManager->persist($trajet);
        $entityManager->flush();

        // Renvoyer le trajet créé au format JSON avec le code HTTP 201.
        return $this->json($this->serializeTrajet($trajet), Response::HTTP_CREATED);
    }


    // ========================================================================
    //                        ✨AFFICHER LES DÉTAILS D'UN TRAJET
    // ========================================================================

    #[Route('api/trajets/{id}', name: 'app_trajet_show', methods: ['GET'])]
    public function showTrajetById(int $id, TrajetRepository $repository): JsonResponse
    {
        // Chercher dans la base de donnée un trajet via son id
        $trajet = $repository->find($id);

        // Si le produit n'existe pas, retourner une erreur 404.
        if (!$trajet) {
            return $this->errorResponse('Reservation not found.', Response::HTTP_NOT_FOUND);
        }

        // Sinon retouner le détails du produit avec le status HTTP 302
        return $this->json($this->serializeTrajet($trajet), Response::HTTP_FOUND);
    }

    // ========================================================================
    //                        ✨MODIFIER UN TRAJET
    // ========================================================================

    #[Route('api/trajets/{id}/update', name: 'app_trajet_edit', methods: ['PUT', 'PATCH'])]// PUT: Mettre à jour ou remplacer une ressource | PATCH : Modifier partiellement une ressource
    public function updateTrajet(
        int $id,
        Request $request,
        TrajetRepository $repository,
        EntityManagerInterface $entityManager,
        OpenStreetMapService $osm
    ): JsonResponse {
        // Chercher dans la base de donnée le trajet à modifier via son id 
        $trajet = $repository->find($id);

        // Si le produit n'existe pas, retourner une erreur 404.
        if (!$trajet) {
            return $this->errorResponse('trajet not found.', Response::HTTP_NOT_FOUND);
        }

        // Décode le JSON de la requête de modification
        $data = $this->decodeJson($request);

        // Si ce n'est pas un tableau ou qu'il y a une erreur JSON
        if ($data === null) {
            // Alors retourner message d'erreur avec code HTTP 400
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();

        if ($user->getId() !== $trajet->getUser()->getId()) {
            return $this->errorResponse("Unauthorized: Vous n'êtes pas autorisé à modifier ce trajet  ", Response::HTTP_UNAUTHORIZED);
        }

        $adresseModifiee = false;

        foreach ($data as $key => $value) {

            // Si la valeur est null, on ne touche pas au trajet
            if ($value === null) {
                continue;
            }

            switch ($key) {

                case 'lieu_de_depart':
                    $coord = $osm->geocode($value);
                    if (!$coord || !isset($coord['lat'], $coord['lon'])) {
                        return $this->errorResponse("Adresse invalide pour le départ", 400);
                    }

                    $trajet->setLieuDepartConducteur($value)
                        ->setLatitudeLieuDepartConducteur($coord['lat'])
                        ->setLongitudeLieuDepartConducteur($coord['lon']);

                    $adresseModifiee = true;
                    break;

                case 'lieu_arrivee':
                    $coord = $osm->geocode($value);
                    if (!$coord || !isset($coord['lat'], $coord['lon'])) {
                        return $this->errorResponse("Adresse invalide pour l'arrivée", 400);
                    }

                    $trajet->setLieuArriveeConducteur($value)
                        ->setLatitudeLieuArriveConducteur($coord['lat'])
                        ->setLongitudeLieuArriveConducteur($coord['lon']);

                    $adresseModifiee = true;
                    break;


                case 'nombre_de_place':
                    $places = $this->parseInt($value, $error);
                    if ($places === null || $places < 1) {
                        return $this->errorResponse($error ?? 'Number of places must be at least 1.', 400);
                    }
                    $trajet->setNombreDePlace($places);
                    break;

                case 'prix':
                    $prix = $this->parseFloat($data, $value, true, $error);
                    if ($prix === null) {
                        return $this->errorResponse($error ?? 'Price invalid.', 400);
                    }
                    $trajet->setPrix($prix);
                    break;
                case 'nature_trajet':
                    try {
                        $nature_trajet = NatureTrajet::from($value);
                    } catch (\ValueError $e) {
                        return $this->errorResponse(
                            'Nature of the journey must be Offre or Demande.',
                            Response::HTTP_BAD_REQUEST
                        );
                    }

                case 'type_trajet':
                    try {
                        $type_trajet = TypeTrajet::from($value);
                    } catch (\ValueError $e) {
                        return $this->errorResponse(
                            'Type of the journey must be "Domicile Travail" or "Évènement" .',
                            Response::HTTP_BAD_REQUEST
                        );
                    }

                case 'statut_valide':
                    try {
                        $statut_valide = StatutValidTrajet::from($value);
                    } catch (\ValueError $e) {
                        return $this->errorResponse(
                            'Statut of the journey must be "En attente", "Valide" , or "Refusé" .',
                            Response::HTTP_BAD_REQUEST
                        );
                    }
            }
        }

        // 🔥 Recalcul une seule fois à la fin
        if ($adresseModifiee) {

            $points = [
                [
                    'lat' => $trajet->getLatitudeLieuDepartConducteur(),
                    'lon' => $trajet->getLongitudeLieuDepartConducteur()
                ],
                [
                    'lat' => $trajet->getLatitudeLieuArriveConducteur(),
                    'lon' => $trajet->getLongitudeLieuArriveConducteur()
                ]
            ];

            $donnees = $osm->donneesTrajet($points);

            $trajet->setDuree($donnees['durationMin']);
            $trajet->setNombreDeKm($donnees['distanceKm']);
        }

        $entityManager->flush();

        // Sinon retouner le détails du produit avec le status HTTP 302
        return $this->json($this->serializeTrajet($trajet), Response::HTTP_FOUND);
    }

    #[Route('/api/trajets/{id}', name: 'app_trajet_delete', methods: ['DELETE'])]
    public function supprimerTrajet(int $id, Request $request, TrajetRepository $repository, EntityManagerInterface $entityManager): Response
    {
        
        $trajet = $repository->find($id);

        if (!$trajet) {
            return $this->errorResponse('Trajet not found.', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($trajet);
        $entityManager->flush();
        

        return $this->redirectToRoute('app_trajets_list', [], Response::HTTP_SEE_OTHER);
    }
    // ========================================================================
    //                        MÉTHODES PRIVÉES
    // ========================================================================

    /**
     * Transforme une chaîne JSON en tableau associatif PHP
     * 
     */
    private function decodeJson(Request $request): ?array
    {
        // Le paramètre true force la conversion en tableau (sinon ce serait un objet)
        $payload = json_decode($request->getContent(), true);

        // Vérifie que c'est bien un tableau ET qu'il n'y a pas d'erreur JSON
        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $payload;
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return $this->json(['error' => $message], $status);
    }

    /**
     * Transforme un objet Trajet en tableau associatif
     * @param Trajet $trajet: L'objet Trajet à convertir
     */
    private function serializeTrajet(Trajet $trajet): array
    {
        return [
            'id' => $trajet->getId(),
            'date_de_depart' => $trajet->getDateDeDepart()->format('Y-m-d H:i:s'),
            'lieu_depart' => $trajet->getLieuDepartConducteur(),
            'latitude_lieu_depart_conducteur' => $trajet->getLatitudeLieuDepartConducteur(),
            'longitude_lieu_depart_conducteur' => $trajet->getLongitudeLieuDepartConducteur(),
            'lieu_arrivee' => $trajet->getLieuArriveeConducteur(),
            'latitude_lieu_arrive_conducteur' => $trajet->getLatitudeLieuArriveConducteur(),
            'longitude_lieu_arrive_conducteur' => $trajet->getLongitudeLieuArriveConducteur(),
            'duree' => $trajet->getDuree(),
            'nombre_de_km' => $trajet->getNombreDeKm(),
            'nombre_de_place' => $trajet->getNombreDePlace(),
            'prix' => $trajet->getPrix(),
            'date_de_publication' => $trajet->getDateDePublication()->format('Y-m-d H:i:s'),
            'nature_trajet' => $trajet->getNatureTrajet()->value,
            'type_trajet' => $trajet->getTypeTrajet()->value,
            'statut_valide' => $trajet->getStatutValide()->value,
            'user' => [
                'id' => $trajet->getUser()->getId(),
                'nom' => $trajet->getUser()->getNom(),
                'prenom' => $trajet->getUser()->getPrenom(),
            ],
        ];
    }


    /**
     * Valide et vérifie la donnée de type décimale (exemple : coordonnées GPS et prix)
     * @param mixed $data : tableau de données extrait de la requête
     * @param mixed $value : valeur d'une key du tableau $data
     * @param bool $required : est-ce que que la valeur est obligatoire ?
     * @param mixed $error : message d'erreur
     * @return string|null
     */
    private function parseFloat(array $data, mixed $value, bool $required, ?string &$error): ?string
    {
        if ($value === null || $value === '') {
            if ($required) {
                $error = 'prix is required.';
            }
            return null;
        }

        if (!is_numeric($value)) {
            $error = 'prix must be numeric.';
            return null;
        }

        $prix = (float) $value;

        if ($prix < 0) {
            $error = 'prix must be positive.';
            return null;
        }

        return number_format($prix, 2, '.', '');
    }



    /**
     * Valide et vérifie la donnée de type integer
     * @param array $value
     * @return void
     */
    private function parseInt(mixed $value, ?string $error): ?int
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

    /**
     * Valide et vérifie la donnée par rapport à un enum
     * @return void
     * https://symfony.com/doc/current/ai/components/agent.html#automatic-enum-validation
     */
    private function parseEnum()
    {

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




}





