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
            $codes = $advertisement->getSides();

            if ($codes === []) {
                if ($advertisement->getSideADescription() !== null || $advertisement->getSideAPrice() !== null || $advertisement->getSideAImage() !== null) {
                    $codes[] = 'A';
                }
                if ($advertisement->getSideBDescription() !== null || $advertisement->getSideBPrice() !== null || $advertisement->getSideBImage() !== null) {
                    $codes[] = 'B';
                }
            }

            return array_map(function (string $code) use ($advertisement): array {
                $isA = $code === 'A';

                return [
                    'code' => $code,
                    'description' => $isA ? $advertisement->getSideADescription() : ($code === 'B' ? $advertisement->getSideBDescription() : null),
                    'price' => $isA ? $advertisement->getSideAPrice() : ($code === 'B' ? $advertisement->getSideBPrice() : null),
                    'image' => $isA ? $advertisement->getSideAImage() : ($code === 'B' ? $advertisement->getSideBImage() : null),
                    'image_url' => $this->buildImageUrl($isA ? $advertisement->getSideAImage() : ($code === 'B' ? $advertisement->getSideBImage() : null)),
                ];
            }, $codes);
        }

        usort($sideItems, static fn (AdvertisementSide $left, AdvertisementSide $right): int => strcmp((string) $left->getCode(), (string) $right->getCode()));

        return array_map(function (AdvertisementSide $side) use ($advertisement): array {
            $code = (string) $side->getCode();
            $isA = $code === 'A';

            $description = $side->getDescription() ?? ($isA ? $advertisement->getSideADescription() : ($code === 'B' ? $advertisement->getSideBDescription() : null));
            $price = $side->getPrice() ?? ($isA ? $advertisement->getSideAPrice() : ($code === 'B' ? $advertisement->getSideBPrice() : null));
            $image = $side->getImage() ?? ($isA ? $advertisement->getSideAImage() : ($code === 'B' ? $advertisement->getSideBImage() : null));

            return [
                'code' => $code,
                'description' => $description,
                'price' => $price,
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
