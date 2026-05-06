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

        $updatedSides = 0;
        $updatedLocations = 0;
        $createdAds = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $placeNumber = trim((string) ($row['column_1'] ?? ''));
            $sideCode = mb_strtoupper(trim((string) ($row['column_2'] ?? '')));

            if ($placeNumber === '' || $sideCode === '') {
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

            $ad->addSide($sideCode);
            $side = $ad->getSideByCode($sideCode);

            if (!$side instanceof AdvertisementSide) {
                $side = (new AdvertisementSide())->setCode($sideCode);
                $ad->addSideItem($side);
            }

            if ($image = $this->nullableString($row['column_5'] ?? null)) {
                $side->setImage($image);
            }

            if ($desc = $this->nullableString($row['column_6'] ?? null)) {
                $side->setDescription($desc);
            }

            if ($price = $this->normalizePrice($row['column_8'] ?? null)) {
                $side->setPrice($price);
            }

            $manager->persist($side);
            $manager->persist($ad);
            $updatedSides++;

            [$lat, $lon] = $this->parseCoordinates($row['column_7'] ?? null);
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
            "✅ Стороны: %d; локации: %d; создано объявлений: %d; пропущено: %d\n",
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

    private function findAdvertisement(ObjectManager $manager, string $placeNumber): ?Advertisement
    {
        $repo = $manager->getRepository(Advertisement::class);

        $variants = array_unique([
            $placeNumber,
            trim($placeNumber),
            preg_replace('/\.0+$/', '', trim($placeNumber)),
        ]);

        foreach ($variants as $variant) {
            if (!$variant) continue;

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
        if (!$typeName) {
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

    private function resolveDataFilePath(): ?string
    {
        $paths = [
            __DIR__ . '/data/output.json',
            dirname(__DIR__, 2) . '/fixtures/data/output.json',
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function nullableString(mixed $value): ?string
    {
        $v = trim((string) $value);
        return $v === '' ? null : $v;
    }

    private function normalizePrice(mixed $value): ?string
    {
        if (!$value) return null;

        $v = preg_replace('/[^\d,\.]/u', '', (string) $value);

        if (str_contains($v, ',') && !str_contains($v, '.')) {
            $v = str_replace(',', '.', $v);
        } elseif (str_contains($v, ',') && str_contains($v, '.')) {
            $v = str_replace(',', '', $v);
        }

        return is_numeric($v) ? number_format((float) $v, 2, '.', '') : null;
    }

    private function parseCoordinates(mixed $value): array
    {
        if (!$value) return [null, null];

        $parts = array_map('trim', explode(',', (string) $value));

        if (count($parts) < 2) {
            return [null, null];
        }

        return [$this->toFloat($parts[0]), $this->toFloat($parts[1])];
    }

    private function toFloat(string $value): ?float
    {
        $v = str_replace([' ', ','], ['', '.'], $value);
        return is_numeric($v) ? (float) $v : null;
    }
}