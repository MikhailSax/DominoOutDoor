<?php

namespace App\Command;

use App\Entity\Advertisement;
use App\Entity\AdvertisementLocation;
use App\Entity\AdvertisementType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import:ads-from-json',
    description: 'Импортирует advertisements.json в таблицу advertisement'
)]
class ImportAdvertisementsFromJsonCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $adsPath = __DIR__ . '/../DataFixtures/data/advertisements.json';
        $typesPath = __DIR__ . '/../DataFixtures/data/advertisement_types.json';

        if (!file_exists($adsPath) || !file_exists($typesPath)) {
            $io->error('JSON files not found in src/DataFixtures/data/. Place advertisement_types.json and advertisements.json there.');
            return Command::FAILURE;
        }

        $typesJson = json_decode((string)file_get_contents($typesPath), true);
        $adsJson = json_decode((string)file_get_contents($adsPath), true);

        // Составим карту json type_id -> type name
        $typeIdToName = [];
        foreach ($typesJson as $t) {
            $typeIdToName[$t['id']] = $t['name'];
        }

        $typeRepo = $this->em->getRepository(AdvertisementType::class);

        $count = 0;
        foreach ($adsJson as $row) {
            // ожидаем keys: place_number, address, sides (array), type_id, возможно latitude/longitude
            $placeNumber = (string)($row['place_number'] ?? '');
            $address = $row['address'] ?? null;
            $sides = is_array($row['sides']) ? $row['sides'] : (array)($row['sides'] ?? []);
            $typeId = $row['type_id'] ?? null;

            if (!$placeNumber || !$typeId) {
                // пропускаем неверные записи
                continue;
            }

            $typeName = $typeIdToName[$typeId] ?? null;
            if (!$typeName) {
                $io->warning("Type id {$typeId} not found in advertisement_types.json. Skipping {$placeNumber}.");
                continue;
            }

            /** @var AdvertisementType|null $type */
            $type = $typeRepo->findOneBy(['name' => $typeName]);
            if (!$type) {
                $io->warning("Type not found in DB by name '{$typeName}'. Skipping {$placeNumber}.");
                continue;
            }

            // Проверим — если объявление для этого placeNumber уже есть — обновим sides (merge)
            $existing = $this->em->getRepository(Advertisement::class)->findOneBy(['placeNumber' => $placeNumber]);
            if ($existing) {
                // объединяем стороны
                $existingSides = $existing->getSides();
                $merged = array_values(array_unique(array_merge($existingSides, $sides)));
                $existing->setSides($merged);
                // можно обновить адрес/тип если нужно
                $existing->setType($type);
                if ($address) $existing->setAddress($address);
                $this->em->persist($existing);
                $count++;
                continue;
            }

            // Создаём новое объявление
            $ad = new Advertisement();
            $ad->setPlaceNumber($placeNumber);
            // Можно хранить code как placeNumber или комбинировать:
            $ad->setCode($placeNumber);
            $ad->setAddress($address);
            $ad->setSides($sides);
            $ad->setType($type);

            // Если в JSON есть lat/lng, создаём location (опционально)
            if (isset($row['latitude']) && isset($row['longitude'])) {
                $loc = new AdvertisementLocation();
                $loc->setLatitude((float)$row['latitude']);
                $loc->setLongitude((float)$row['longitude']);
                $loc->setAzimuth(isset($row['azimuth']) ? (int)$row['azimuth'] : null);
                $loc->setAdvertisement($ad);
                $ad->setLocation($loc);
                $this->em->persist($loc);
            }

            $this->em->persist($ad);
            $count++;
        }

        $this->em->flush();
        $io->success("Импортировано/обновлено {$count} объявлений.");

        return Command::SUCCESS;
    }
}
