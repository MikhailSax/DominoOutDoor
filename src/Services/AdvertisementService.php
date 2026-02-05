<?php

namespace App\Services;

use App\Entity\Advertisement;
use App\Entity\AdvertisementSide;

class AdvertisementService
{
    /**
     * @param Advertisement[] $ads
     */
    public function getData(array $ads): array
    {
        return array_map(function (Advertisement $item): array {
            $sideDetails = $this->serializeSides($item);

            return [
                'id' => $item->getId(),
                'category' => $item->getType()?->getCategory()?->getName(),
                'category_id' => $item->getType()?->getCategory()?->getId(),
                'type' => $item->getType()?->getName(),
                'type_id' => $item->getType()?->getId(),
                'place_number' => $item->getPlaceNumber(),
                'sides' => array_column($sideDetails, 'code'),
                'side_details' => $sideDetails,
                'code' => $item->getCode(),
                'address' => $item->getAddress(),
                'location' => [
                    'latitude' => $item->getLocation()?->getLatitude(),
                    'longitude' => $item->getLocation()?->getLongitude(),
                ],
            ];
        }, $ads);
    }

    /**
     * @return array<int, array{code: string, description: ?string, price: ?string, image: ?string, image_url: ?string}>
     */
    private function serializeSides(Advertisement $advertisement): array
    {
        $sideItems = $advertisement->getSideItems()->toArray();

        if ($sideItems === []) {
            return array_map(function (string $code) use ($advertisement): array {
                $isA = $code === 'A';

                return [
                    'code' => $code,
                    'description' => $isA ? $advertisement->getSideADescription() : $advertisement->getSideBDescription(),
                    'price' => $isA ? $advertisement->getSideAPrice() : $advertisement->getSideBPrice(),
                    'image' => $isA ? $advertisement->getSideAImage() : $advertisement->getSideBImage(),
                    'image_url' => $this->buildImageUrl($isA ? $advertisement->getSideAImage() : $advertisement->getSideBImage()),
                ];
            }, $advertisement->getSides());
        }

        usort($sideItems, static fn (AdvertisementSide $left, AdvertisementSide $right): int => strcmp((string) $left->getCode(), (string) $right->getCode()));

        return array_map(function (AdvertisementSide $side): array {
            $image = $side->getImage();

            return [
                'code' => (string) $side->getCode(),
                'description' => $side->getDescription(),
                'price' => $side->getPrice(),
                'image' => $image,
                'image_url' => $this->buildImageUrl($image),
            ];
        }, $sideItems);
    }

    private function buildImageUrl(?string $image): ?string
    {
        if ($image === null || $image === '') {
            return null;
        }

        return '/uploads/advertisements/' . ltrim($image, '/');
    }
}
