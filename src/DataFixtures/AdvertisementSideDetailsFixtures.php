<?php

namespace App\DataFixtures;

use App\Entity\Advertisement;
use App\Entity\AdvertisementLocation;
use App\Entity\AdvertisementSide;
use App\Entity\AdvertisementType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AdvertisementSideDetailsFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $path = $this->resolveDataFilePath();
        if ($path === null) {
            throw new \RuntimeException('Файл output.json не найден.');
        }

        $rows = json_decode((string) file_get_contents($path), true);
        if (!is_array($rows)) {
            throw new \RuntimeException(sprintf('Некорректный JSON в %s', $path));
        }

        if (isset($rows['data']) && is_array($rows['data'])) {
            $rows = $rows['data'];
        }

        $updatedSides = 0;
        $updatedLocations = 0;
        $createdAds = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $placeNumber = $this->extractPlaceNumber($row);
            if ($placeNumber === '') {
                continue;
            }

            $sideDetails = $this->extractSideDetailsRows($row);
            if ($sideDetails === []) {
                continue;
            }

            $ad = $this->findAdvertisement($manager, $placeNumber);

            if (!$ad instanceof Advertisement) {
                $typeName = $this->nullableString($row['column_4'] ?? null);
                $type = $this->findType($manager, $typeName);

                if (!$type instanceof AdvertisementType) {
                    $skipped++;
                    continue;
                }

                $ad = (new Advertisement())
                    ->setPlaceNumber($placeNumber)
                    ->setCode($placeNumber)
                    ->setType($type);

                $manager->persist($ad);
                $createdAds++;
            }

            $address = $this->nullableString($row['column_3'] ?? null);
            if ($address !== null) {
                $ad->setAddress($address);
            }

            foreach ($sideDetails as $detail) {
                $sideCode = mb_strtoupper(trim((string) ($detail['code'] ?? '')));
                if ($sideCode === '') {
                    continue;
                }

                $ad->addSide($sideCode);

                $side = $ad->getSideByCode($sideCode);
                if (!$side instanceof AdvertisementSide) {
                    $side = (new AdvertisementSide())->setCode($sideCode);
                    $ad->addSideItem($side);
                }

                $image = $this->nullableString($detail['image'] ?? null);
                if ($image !== null) {
                    $side->setImage($image);
                    $this->syncLegacySideImage($ad, $sideCode, $image);
                }

                $description = $this->nullableString($detail['description'] ?? null);
                if ($description !== null) {
                    $side->setDescription($description);
                    $this->syncLegacySideDescription($ad, $sideCode, $description);
                }

                $price = $this->normalizePrice($detail['price'] ?? null);
                if ($price !== null) {
                    $side->setPrice($price);
                    $this->syncLegacySidePrice($ad, $sideCode, $price);
                }

                $manager->persist($side);
                $updatedSides++;
            }

            $manager->persist($ad);

            [$lat, $lon] = $this->extractCoordinates($row);

            if ($lat !== null && $lon !== null) {
                $location = $ad->getLocation();

                if (!$location instanceof AdvertisementLocation) {
                    $location = new AdvertisementLocation();
                    $location->setAdvertisement($ad);
                    $ad->setLocation($location);
                }

                $location->setLatitude($lat);
                $location->setLongitude($lon);

                $manager->persist($location);
                $updatedLocations++;
            }
        }

        $manager->flush();

        echo sprintf(
            "✅ Обновлено сторон: %d; локаций: %d; создано объявлений: %d; пропущено: %d\n",
            $updatedSides,
            $updatedLocations,
            $createdAds,
            $skipped
        );
    }

    public function getDependencies(): array
    {
        return [AdvertisementsFixtures::class];
    }

    public static function getGroups(): array
    {
        return ['side_details'];
    }

    private function extractPlaceNumber(array $row): string
    {
        return $this->normalizePlaceNumber($row['place_number'] ?? $row['column_1'] ?? null);
    }

    private function extractSideDetailsRows(array $row): array
    {
        if (isset($row['side_details']) && is_array($row['side_details'])) {
            return array_values(array_filter($row['side_details'], static fn ($v) => is_array($v)));
        }

        return [[
            'code' => $row['column_2'] ?? null,
            'description' => $row['column_6'] ?? null,
            'image' => $row['column_5'] ?? null,
            'price' => $row['column_8'] ?? null,
        ]];
    }

    private function extractCoordinates(array $row): array
    {
        if (isset($row['latitude']) || isset($row['longitude'])) {
            return [
                $this->toFloat((string) ($row['latitude'] ?? '')),
                $this->toFloat((string) ($row['longitude'] ?? '')),
            ];
        }

        return $this->parseCoordinates($row['column_7'] ?? null);
    }

    private function findAdvertisement(ObjectManager $manager, string $placeNumber): ?Advertisement
    {
        $repo = $manager->getRepository(Advertisement::class);

        $variants = array_values(array_unique(array_filter([
            $this->normalizePlaceNumber($placeNumber),
            trim($placeNumber),
            preg_replace('/\.0+$/', '', trim($placeNumber)),
        ], static fn ($v) => is_string($v) && $v !== '')));

        foreach ($variants as $variant) {
            if ($ad = $repo->findOneBy(['placeNumber' => $variant])) {
                return $ad;
            }

            if ($ad = $repo->findOneBy(['code' => $variant])) {
                return $ad;
            }
        }

        return null;
    }

    private function findType(ObjectManager $manager, ?string $typeName): ?AdvertisementType
    {
        if ($typeName === null || trim($typeName) === '') {
            return null;
        }

        $normalized = mb_strtolower(trim($typeName));
        $repo = $manager->getRepository(AdvertisementType::class);

        if ($exact = $repo->findOneBy(['name' => trim($typeName)])) {
            return $exact;
        }

        foreach ($repo->findAll() as $type) {
            if (mb_strtolower(trim($type->getName())) === $normalized) {
                return $type;
            }
        }

        return null;
    }

    private function normalizePlaceNumber(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $raw = trim((string) $value);
        $raw = preg_replace('/\x{00A0}/u', ' ', $raw) ?? $raw;
        $raw = trim($raw);

        return (string) preg_replace('/\.0+$/', '', $raw);
    }

    private function nullableString(mixed $value): ?string
    {
        $v = trim((string) $value);
        return $v === '' ? null : $v;
    }

    private function normalizePrice(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = preg_replace('/[^\d,\.]/u', '', (string) $value);

        if (str_contains($normalized, ',') && !str_contains($normalized, '.')) {
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace(',', '', $normalized);
        }

        return is_numeric($normalized)
            ? number_format((float) $normalized, 2, '.', '')
            : null;
    }

    private function parseCoordinates(mixed $value): array
    {
        if ($value === null) {
            return [null, null];
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return [null, null];
        }

        $parts = array_map('trim', explode(',', $raw));
        if (count($parts) < 2) {
            return [null, null];
        }

        return [
            $this->toFloat($parts[0]),
            $this->toFloat($parts[1]),
        ];
    }

    private function toFloat(string $value): ?float
    {
        $normalized = str_replace([' ', ','], ['', '.'], $value);
        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function syncLegacySideDescription(Advertisement $ad, string $sideCode, string $description): void
    {
        if ($sideCode === 'A') {
            $ad->setSideADescription($description);
        }

        if ($sideCode === 'B') {
            $ad->setSideBDescription($description);
        }
    }

    private function syncLegacySidePrice(Advertisement $ad, string $sideCode, string $price): void
    {
        if ($sideCode === 'A') {
            $ad->setSideAPrice($price);
        }

        if ($sideCode === 'B') {
            $ad->setSideBPrice($price);
        }
    }

    private function syncLegacySideImage(Advertisement $ad, string $sideCode, string $image): void
    {
        if ($sideCode === 'A') {
            $ad->setSideAImage($image);
        }

        if ($sideCode === 'B') {
            $ad->setSideBImage($image);
        }
    }

    private function resolveDataFilePath(): ?string
    {
        $candidates = [
            __DIR__ . '/data/output.json',
            dirname(__DIR__, 2) . '/fixtures/data/output.json',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
