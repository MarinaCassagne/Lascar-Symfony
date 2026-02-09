<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface; 
// Interface Symfony permettant d'effectuer des requêtes HTTP (GET, POST, etc.)

/**
 * Service permettant d'interagir avec les APIs OpenStreetMap (Nominatim et OSRM)
 */
class OpenStreetMapService
{
    /**
     * Injection du client HTTP via le constructeur
     * Symfony se charge automatiquement de fournir l'implémentation
     */
    public function __construct(
        private HttpClientInterface $client
    ) {
    }

    /**
     * Récupère les coordonnées GPS (latitude et longitude) à partir d'une adresse
     *
     * @param string $adresse Adresse à géocoder
     * @return array{lat: float, lon: float}
     * @throws \Exception Si aucune adresse n'est trouvée
     *
     * Cette méthode utilise l'API Nominatim d'OpenStreetMap
     */
    public function geocode(string $adresse): array
    {
        // Envoi de la requête HTTP GET vers l'API Nominatim
        $response = $this->client->request(
            'GET',
            'https://nominatim.openstreetmap.org/search',
            [
                'query' => [
                    'q' => $adresse,   // Adresse recherchée
                    'format' => 'json',// Format de la réponse
                    'limit' => 1,      // On ne récupère qu'un seul résultat
                ],
                // User-Agent obligatoire pour respecter les règles de Nominatim
                'headers' => [
                    'User-Agent' => 'Lascar/1.0'
                ]
            ]
        );

        // Conversion de la réponse JSON en tableau PHP
        $data = $response->toArray();

        // Si aucun résultat n'est retourné, on lève une exception
        if (empty($data)) {
            throw new \Exception('Adresse introuvable');
        }

        // Retourne uniquement la latitude et la longitude du premier résultat
        return [
            'lat' => (float) $data[0]['lat'],
            'lon' => (float) $data[0]['lon'],
        ];
    }

    /**
     * Calcule la distance et la durée d'un trajet entre plusieurs points GPS
     *
     * @param array $points Tableau de points contenant 'lat' et 'lon'
     * @return array{distanceKm: float|int, durationMin: float|int}
     *
     * Cette méthode utilise l'API OSRM (Open Source Routing Machine)
     */
    public function donneesTrajet(array $points): array
    {
        // Transformation des points en format "longitude,latitude"
        // requis par l'API OSRM
        $coords = array_map(
            fn ($p) => "{$p['lon']},{$p['lat']}",
            $points
        );

        // Construction de l'URL OSRM
        // Les coordonnées sont séparées par des ";"
        // Exemple :
        // https://router.project-osrm.org/route/v1/driving/lon1,lat1;lon2,lat2
        $url = 'https://router.project-osrm.org/route/v1/driving/' . implode(';', $coords);

        // Envoi de la requête HTTP vers l'API OSRM
        $response = $this->client->request('GET', $url, [
            'query' => [
                // Overview = dessin du trajet qu'on lui passera:
                // Point A: Paris , Point B: Marseille
                // Overview envoie dans la réponse 
                // {
                //  "geometry": 'lien polilyne'
                // }
                // Ce lien peut être utiliser avec leaflet ou d'autre outil pour afficher le trajet sur une map.
                'overview' => 'false'  
            ]
        ]);

        // Conversion de la réponse JSON en tableau PHP
        $data = $response->toArray();

        // Récupération du premier trajet proposé par l'API
        $route = $data['routes'][0];

        // Retour de la distance (en km) et de la durée (en minutes)
        return [
            'distanceKm' => $route['distance'] / 1000, // mètres → kilomètres
            'durationMin' => $route['duration'] / 60,  // secondes → minutes
        ];
    }
}
