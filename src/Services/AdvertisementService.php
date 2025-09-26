<?php

namespace App\Services;

use App\Repository\AdvertisementRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdvertisementService
{
    public function getData(array $ads): array
    {
        $result = array_map(fn($item) => [
            'id' => $item['id'],
            'category' => $item['category'],
            'type' => $item['type'],
            'place_number' => $item['place_number'],
            'sides' => $item['side'],
            'code' => $item['code'],
            'address' => $item['address'],
            'location' => [
                'latitude' => $item['latitude'],
                'longitude' => $item['longitude']
            ]

        ], $ads);
        return $result;
    }
}
