<?php

namespace App\Services;

class AdvertisementService
{
    public function getData(array $ads): array
    {
        return array_map(static function (array $item): array {
            return [
                'id' => $item['id'],
                'category' => $item['category'],
                'category_id' => $item['category_id'] ?? null,
                'type' => $item['type'],
                'type_id' => $item['type_id'] ?? null,
                'place_number' => $item['place_number'],
                'sides' => is_array($item['side'] ?? null) ? $item['side'] : [],
                'code' => $item['code'],
                'address' => $item['address'],
                'location' => [
                    'latitude' => isset($item['latitude']) ? (float) $item['latitude'] : null,
                    'longitude' => isset($item['longitude']) ? (float) $item['longitude'] : null,
                ],
            ];
        }, $ads);
    }
}
