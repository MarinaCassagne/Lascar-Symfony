<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenStreetMapService
{

    public function __construct(
        private HttpClientInterface $client
    ) {
    }

    public function geocode(string $adresse): array
    {
        $response = $this->client->request('GET', 'https://nominatim.openstreetmap.org/search', ['query' => ['q' => $adresse, 'format' => 'json', 'limit' => 1,], 'headers' => ['User-Agent' => 'Lascar/1.0']]);
        $data =$response->toArray();

        if(empty($data)){
            throw new \Exception('Adresse introuvable');
        }

        return [
            'lat'=>(float) $data[0]['lat'],
            'lon'=>(float) $data[0]['lon'],
        ];
    }

    public function donneesTrajet(array $points): array{

        $coords = array_map(
            fn ($p) => "{$p['lon']},{$p['lat']}",
            $points
        );

        $url = 'https://router.project-osrm.org/route/v1/driving/'.implode(';', $coords);

        $response = $this->client->request('GET', $url, [
            'query'=>[
                'overview' => 'false'
            ]
        ]);

        $data = $response->toArray();

        $route = $data['routes'][0];

        return [
            'distanceKm' => $route['distance'] / 1000,
            'durationMin' => $route['duration']/ 60,
        ];
    }


}



