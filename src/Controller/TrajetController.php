<?php

namespace App\Controller;

use App\Entity\EtapeTrajet;
use App\Entity\Trajet; // Entité Trajet pour pouvoir instancier un trajet
use App\Entity\User; // Entité User pour pouvoir instancier récupérer IdUser
use App\Entity\Moderation; // Entité Moderation pour pouvoir instancier récupérer IdModeration
use App\Entity\Reservation;
use App\Form\TrajetType;
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
    public function listTrajets(TrajetRepository $trajetRepository): JsonResponse
    {
        // Permet de mettre dans un tableau le résulat de la fonction 
        $trajets = array_map(

            fn(Trajet $trajet) => $this->serializeTrajet($trajet),

            // Récupère tous les trajets de la BDD
            $trajetRepository->findAll()
        );

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
        // ==============================================

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

        // Je récupère les coordonnées du lieu de départ
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

        //============= "ADRESSES" =====================

        // TODO ⚠️ AJOUTER LA VÉRIFICATION : SI LES ADRESSES EXISTENT
        // TODO AJOUTER  les attributs $adresse_lieu_depart_conducteur et $adresse_lieu_arrive_conducteur

        //=== ADRESSE LIEU DE DÉPART================

        // Récupérer la donnée longitude_lieu_depart_conducteur dans la requête
        $longitude_lieu_depart_conducteur = $coordDepart['lon'];


        // Récupérer la donnée latitude_lieu_depart_conducteur dans la requête
        $latitude_lieu_depart_conducteur = $coordDepart['lat'];


        //=== ADRESSE LIEU DE D'ARRIVÉE ============

        // Récupérer la donnée longitude_lieu_arrive_conducteur dans la requête
        $longitude_lieu_arrive_conducteur = $coordArrivee['lon'];

        // Récupérer la donnée latitude_lieu_arrive_conducteur dans la requête
        $latitude_lieu_arrive_conducteur = $coordArrivee['lat'];


        //============= DURÉE EN MINUTES =========================

        // TODO UTILISER API POUR CALCULER LA DURÉE EN UTILISANT LES COORDONNÉES GPS DU LIEU DE DÉPART ET D'ARRIVÉE
        //  Ressource :https://distancematrix.ai/fr/guides/easy-migrate

        // Récupérer la donnée durée dans la requête
        $duree = $data['duree'] ?? '';

        // Nettoyer et valider la data duree
        if ($duree === '') {
            // Si la duree est vide, retourner une erreur avec le code HTTP (HperTexte Transfer Protocol) 400.
            return $this->errorResponse('Duration is required.', Response::HTTP_BAD_REQUEST);
        }

        //============= NOMBRE DE KM ==================
        // TODO UTILISER API POUR CALCULER LE NOMBRE DE KM EN UTILISANT LES COORDONNÉES GPS DU LIEU DE DÉPART ET D'ARRIVÉE

        // Récupérer la donnée nombre de km dans la requête
        $nombre_de_km = $data['nombre_de_km'] ?? '';

        // Nettoyer et valider la donnée nombre de km
        if ($nombre_de_km === '') {
            // Si le nombre de km est vide, retourner une erreur avec le code HTTP (HperTexte Transfer Protocol) 400.
            return $this->errorResponse('Number of kilometers is required.', Response::HTTP_BAD_REQUEST);
        }

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

        //======== STATUT VALIDE TRAJET =============

        // Récupérer le statut valide du trajet dans la requête (EN_ATTENTE, VALIDE, REFUSE)
        $statut_valide = $data['statut_valide'] ?? '';

        // Si le statut valide du trajet n'est pas égale à un élément de l'enum, 
        // TODO https://symfony.com/doc/current/ai/components/agent.html#automatic-enum-validation

        // Alors retourner une erreur.
        // return $this->errorResponse(' Valid trip status is invalid (EN_ATTENTE, VALIDE, REFUSE).', Response::HTTP_BAD_REQUEST);


        // Si le statut du trajet n'est pas renseigné,
        if ($statut_valide === '') {
            // Alors retourner une erreur avec le code HTTP (HperTexte Transfer Protocol) 400.
            return $this->errorResponse('Valid trip status is required.', Response::HTTP_BAD_REQUEST);
        }

        //======== DATE DE PUBLICATION ================

        // Récupérer la date de publication dans la requête
        $date_de_publication = $data['date_de_publication'] ?? '';

        // TODO AJOUTER LA VÉRIFICATION DU FORMAT DE LA DATE : Utiliser DateTimeValidator ?
        // Nettoyer et valider la data $date_de_publication

        // Si la date de publication n'est pas au bon format, 
        // if ()
        // {
        //     Alors retourner une erreur
        //     return $this->errorResponse('Publication date is not format Y-m-d H:i:s.', Response::HTTP_BAD_REQUEST);
        // }

        // Si la date de publication n'est pas renseignée, 
        if ($date_de_publication === '') {
            // Alors retourner une erreur avec le code HTTP (HperTexte Transfer Protocol) 400.
            return $this->errorResponse('Publication date is required.', Response::HTTP_BAD_REQUEST);
        }

        // //======== ID MODÉRATION ========

        // // Récupérer l'id Modération si il en existe une pour ce trajet
        // $idModeration = $entityManager->getRepository(Moderation::class)->find($data['idModeration']);

        // // Renvoyer une message si idModeration avec le code HTTP (HperTexte Transfer Protocol) 400. 
        // if (!$idModeration) {
        //     return $this->errorResponse('Moderation id : {idModeration} not found.', Response::HTTP_BAD_REQUEST);
        // }

        //======== ID USER ========

        if (!isset($data['user_id'])) {
            return $this->errorResponse('User ID is required.', Response::HTTP_BAD_REQUEST);
        }


        // Récupérer l'idUser dans la requête de la personne ayant proposer le trajet
        $User = $entityManager->getRepository(User::class)->find($data['user_id']);

        // Renvoyer une message si idUser non trouvé avec le code HTTP (HperTexte Transfer Protocol) 400.
        if (!$User) {
            return $this->errorResponse('User id : {User} not found.', Response::HTTP_BAD_REQUEST);
        }

        // //======== ID ÉTAPE TRAJET ========

        // // Récupérer l'idEtapeTrajet dans la requête associé à l'i
        // $idEtapeTrajet = $entityManager->getRepository(EtapeTrajet::class)->find($data['idEtateTrajet']);

        // // Renvoyer une message si idEtapeTrajet non trouvé avec le code HTTP (HperTexte Transfer Protocol) 400.
        // if (!$idEtapeTrajet) {
        //     return $this->errorResponse('Journey stage id : {idEtapeTrajet} not found.', Response::HTTP_BAD_REQUEST);
        // }

        // //======== ID RÉSERVATION ========

        // // Récupérer l'idReservation dans la requête associé à l'i
        // $idReservation = $entityManager->getRepository(Reservation::class)->find($data['idReservation']);

        // // Renvoyer une message si idEtapeTrajet non trouvé avec le code HTTP (HperTexte Transfer Protocol) 400.
        // if (!$idReservation) {
        //     return $this->errorResponse('Reservation id : {idReservation} not found.', Response::HTTP_BAD_REQUEST);
        // }

        // ==============================================
        //         CRÉATION D'UN TRAJET 
        // ==============================================    

        // Instancier l'objet trajet
        $trajet = (new Trajet())
            ->setDateDeDepart($date_de_depart)
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
            ->setStatutValide($statut_valide)
            ->setDateDePublication($date_de_publication)
            // ->setIdModeration($idModeration)
            ->setUser($User);
        // ->addIdEtapeTrajet($idEtapeTrajet)
        // ->addIdReservation($idReservation);

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
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Chercher dans la base de donnée le trajet à modifier via son id 
        $trajet = $repository->find($id);

        // Si le produit n'existe pas, retourner une erreur 404.
        if (!$trajet) {
            return $this->errorResponse('Reservation not found.', Response::HTTP_NOT_FOUND);
        }

        // Décode le JSON de la requête de modification
        $data = $this->decodeJson($request);

        // Si ce n'est pas un tableau ou qu'il y a une erreur JSON
        if ($data === null) {
            // Alors retourner message d'erreur avec code HTTP 400
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        // Récupérer la méthode de la requête
        $isPut = $request->getMethod() === 'PUT';


        //=== MODIFIFIER DATE DE DÉPART================
        if (array_key_exists('date_de_depart', $data) || $isPut) {

            $date_de_depart = $this->parseDateTime($data['date_heure_depart'] ?? null, $error);
            ;

            if ($date_de_depart === '') {
                return $this->errorResponse('Departure date is required.', Response::HTTP_BAD_REQUEST);
            }
            $trajet->setDateDeDepart($date_de_depart);
        }

        //=== MODIFIER ADRESSE LIEU DE DÉPART ================
        if (array_key_exists('latitude_lieu_de_depart_conducteur', $data) || $isPut) {
            $value = $this->parseFloat($data, $data['latitude_lieu_depart_conducteur'] ?? null, true, $error);
            if ($value === null) {
                return $this->errorResponse($error ?? 'Latitude starting point driver is invalid.', Response::HTTP_BAD_REQUEST);
            }
            $trajet->setLatitudeLieuDepartConducteur($value);

        }

        if (array_key_exists('longitude_lieu_de_depart_conducteur', $data) || $isPut) {
            $value = $this->parseFloat($data, $data['longitude_lieu_depart_conducteur'] ?? null, true, $error);
            if ($value === null) {
                return $this->errorResponse($error ?? 'Longitude starting point driver is invalid.', Response::HTTP_BAD_REQUEST);
            }
            $trajet->setLongitudeLieuDepartConducteur($value);

        }

        //=== MODIFIER ADRESSE LIEU DE D'ARRIVÉE ========================
        if (array_key_exists('latitude_lieu_arrive_conducteur', $data) || $isPut) {
            $value = $this->parseFloat($data, $data['latitude_lieu_arrive_conducteur'] ?? null, true, $error);
            if ($value === null) {
                return $this->errorResponse($error ?? 'Latitude of the arrival point driver is invalid.', Response::HTTP_BAD_REQUEST);
            }
            $trajet->setLatitudeLieuArriveConducteur($value);

        }

        if (array_key_exists('longitude_lieu_arrive_conducteur', $data) || $isPut) {
            $value = $this->parseFloat($data, $data['longitude_lieu_arrive_conducteur'] ?? null, true, $error);
            if ($value === null) {
                return $this->errorResponse($error ?? 'Longitude of the arrival point driver is invalid.', Response::HTTP_BAD_REQUEST);
            }
            $trajet->setLongitudeLieuArriveConducteur($value);
        }

        //============= MODIFIER DURÉE =========================

        if (array_key_exists('duree', $data) || $isPut) {
            $duree = $this->parseInt($data['duree'] ?? null, $error);
            if ($duree === null || $duree <= 0) {
                return $this->errorResponse($error ?? 'The duration must be greater than 0 minutes', Response::HTTP_BAD_REQUEST);
            }
            $trajet->setDuree($duree);
        }


        //============= NOMBRE DE KM ==================

        if (array_key_exists('nombre_de_km', $data) || $isPut) {
            $nombre_de_km = $this->parseInt($data['nombre_de_km'] ?? null, $error);
            if ($duree === null || $duree <= 0) {
                return $this->errorResponse($error ?? 'The duration must be greater than 0 minutes', Response::HTTP_BAD_REQUEST);
            }
            $trajet->setNombreDeKm($nombre_de_km);
        }

        //============= NOMBRE DE PLACES ===============

        if (array_key_exists('nombre_de_place', $data) || $isPut) {
            $nombre_de_place = $this->parseInt($data['nombre_de_place'] ?? null, $error);
            if ($nombre_de_place === null || $nombre_de_place < 1) {
                return $this->errorResponse($error ?? 'The number of places must be at least 1', Response::HTTP_BAD_REQUEST);
            }
            $trajet->setNombreDeKm($nombre_de_place);
        }

        //============= PRIX ===========================

        if (array_key_exists('prix', $data) || $isPut) {
            $prix = $this->parseFloat($data, $data['prix'] ?? null, true, $error);

            if ($prix === null) {
                // Si le prix est null, retourner une erreur avec le code HTTP (HperTexte Transfer Protocol) 400.
                return $this->errorResponse($error ?? 'Price is invalid.', Response::HTTP_BAD_REQUEST);
            }

            $trajet->setPrix($prix);
        }

        //============= NatureTrajet =========================== 

        // TODO 
        // $nature_trajet
        // $type_trajet
        // $statut_valide
        // $date_de_publication
        // $idModeration
        // $User
        // $idEtapeTrajet
        // $idReservation

        // Sinon retouner le détails du produit avec le status HTTP 302
        return $this->json($this->serializeTrajet($trajet), Response::HTTP_FOUND);
    }

    #[Route('/{id}', name: 'app_trajet_delete', methods: ['POST'])]
    public function delete(Request $request, Trajet $trajet, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $trajet->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($trajet);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_trajet_index', [], Response::HTTP_SEE_OTHER);
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
            'date_de_depart' => $trajet->getDateDeDepart(),
            'longitude_lieu_depart_conducteur' => $trajet->getLongitudeLieuDepartConducteur(),
            'latitude_lieu_depart_conducteur' => $trajet->getLatitudeLieuDepartConducteur(),
            'longitude_lieu_arrive_conducteur' => $trajet->getLongitudeLieuArriveConducteur(),
            'latitude_lieu_arrive_conducteur' => $trajet->getLatitudeLieuArriveConducteur(),
            'duree' => $trajet->getDuree(),
            'nombre_de_km' => $trajet->getNombreDeKm(),
            'nombre_de_place' => $trajet->getNombreDePlace(),
            'prix' => $trajet->getPrix(),
            'date_de_publication' => $trajet->getDateDePublication()->format('Y-m-d H:i:s'),
            'nature_trajet' => $trajet->getNatureTrajet(),
            'type_trajet' => $trajet->getTypeTrajet(),
            'statut_valide' => $trajet->getStatutValide(),
            'idModeration' => $trajet->getIdModeration(),
            'User' => $trajet->getUser(),
            'idEtapeTrajet' => $trajet->getIdEtapeTrajet(),
            'idReservation' => $trajet->getIdReservation()
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





