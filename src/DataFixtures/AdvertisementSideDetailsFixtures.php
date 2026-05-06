<?php

namespace App\DataFixtures;

use App\Entity\Advertisement;
use App\Entity\AdvertisementLocation;
use App\Entity\AdvertisementSide;
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
            throw new \RuntimeException('Файл output.json не найден. Ожидается в src/DataFixtures/data/output.json или fixtures/data/output.json');
        }

        $rows = json_decode((string) file_get_contents($path), true);
        if (!is_array($rows)) {
            throw new \RuntimeException(sprintf('Некорректный JSON в %s', $path));
        }

        $updatedSides = 0;
        $updatedLocations = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $placeNumber = trim((string) ($row['column_1'] ?? ''));
            $sideCode = mb_strtoupper(trim((string) ($row['column_2'] ?? '')));

            if ($placeNumber === '' || $sideCode === '') {
                continue;
            }

            /** @var Advertisement|null $ad */
            $ad = $manager->getRepository(Advertisement::class)->findOneBy(['placeNumber' => $placeNumber]);
            if (!$ad instanceof Advertisement) {
                continue;
            }

            $ad->setAddress($this->nullableString($row['column_3'] ?? null) ?? $ad->getAddress());

            $ad->addSide($sideCode);
            $side = $ad->getSideByCode($sideCode);
            if (!$side instanceof AdvertisementSide) {
                $side = (new AdvertisementSide())->setCode($sideCode);
                $ad->addSideItem($side);
            }

            $side->setImage($this->nullableString($row['column_5'] ?? null));
            $side->setDescription($this->nullableString($row['column_6'] ?? null));
            $side->setPrice($this->normalizePrice($row['column_8'] ?? null));

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

        echo sprintf("✅ Обновлено сторон: %d; локаций: %d\n", $updatedSides, $updatedLocations);
    }

    public function getDependencies(): array
    {
        return [AdvertisementsFixtures::class];
    }

    public static function getGroups(): array
    {
        return ['side_details'];
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

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $v = trim((string) $value);
        return $v === '' ? null : $v;
    }

    private function normalizePrice(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = preg_replace('/[^\d,\.]/u', '', (string) $value);
        if ($normalized === null || $normalized === '') {
            return null;
        }

        if (str_contains($normalized, ',') && !str_contains($normalized, '.')) {
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace(',', '', $normalized);
        }

        return is_numeric($normalized) ? number_format((float) $normalized, 2, '.', '') : null;
    }

    /**
     * @return array{0:?float,1:?float}
     */
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

        $lat = $this->toFloat($parts[0]);
        $lon = $this->toFloat($parts[1]);

        return [$lat, $lon];
    }

    private function toFloat(string $value): ?float
    {
        $normalized = str_replace([' ', ','], ['', '.'], $value);
        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
