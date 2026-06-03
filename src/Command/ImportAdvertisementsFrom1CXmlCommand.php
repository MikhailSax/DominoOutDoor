<?php

namespace App\Command;

use App\Entity\Advertisement;
use App\Entity\AdvertisementCategory;
use App\Entity\AdvertisementLocation;
use App\Entity\AdvertisementType;
use App\Service\OneCAdvertisementXmlParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import:ads-from-1c-xml',
    description: 'Импортирует рекламные конструкции из XML-выгрузки 1С MessageFor_ST*'
)]
class ImportAdvertisementsFrom1CXmlCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OneCAdvertisementXmlParser $parser,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'Путь к XML-файлу 1С', 'MessageFor_ST0000000007.xml')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Только разобрать XML и показать статистику без записи в БД')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Ограничить количество импортируемых конструкций');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = (string) $input->getArgument('file');
        $limit = $input->getOption('limit') !== null ? max(0, (int) $input->getOption('limit')) : null;

        $data = $this->parser->parse($path);
        $io->section('XML 1С прочитан');
        $io->listing([
            sprintf('Размеров: %d', count($data['sizes'])),
            sprintf('Видов сторон: %d', count($data['sideTypes'])),
            sprintf('Типов рекламных блоков: %d', count($data['types'])),
            sprintf('Рекламных конструкций: %d', count($data['advertisements'])),
        ]);

        if ($input->getOption('dry-run')) {
            $io->success('Dry-run завершён: данные разобраны, запись в БД не выполнялась.');

            return Command::SUCCESS;
        }

        $created = 0;
        $updated = 0;
        $processed = 0;
        $typeRepo = $this->em->getRepository(AdvertisementType::class);
        $adRepo = $this->em->getRepository(Advertisement::class);

        foreach ($data['advertisements'] as $row) {
            if ($limit !== null && $processed >= $limit) {
                break;
            }

            $typeName = $row['typeName'] ?? null;
            if ($typeName === null) {
                $io->warning(sprintf('Конструкция %s пропущена: не найден тип рекламного блока.', $row['placeNumber']));
                continue;
            }

            /** @var AdvertisementType|null $type */
            $type = $typeRepo->findOneBy(['name' => $typeName]);
            if ($type === null) {
                $type = (new AdvertisementType())
                    ->setName($typeName)
                    ->setCategory($this->findOrCreateCategory($typeName));
                $this->em->persist($type);
            }
            $type->setOneCRef($row['typeRef'] ?? null);
            if (($row['typeRef'] ?? null) !== null && isset($data['types'][$row['typeRef']])) {
                $type->setOneCData($data['types'][$row['typeRef']]);
            }

            /** @var Advertisement|null $ad */
            $ad = $adRepo->findOneBy(['placeNumber' => $row['placeNumber']]);
            if ($ad === null) {
                $ad = (new Advertisement())->setPlaceNumber($row['placeNumber']);
                $this->em->persist($ad);
                $created++;
            } else {
                $updated++;
            }

            $ad
                ->setCode($row['code'])
                ->setAddress($row['address'])
                ->setOneCRef($row['externalRef'] ?? null)
                ->setOneCData($row)
                ->setType($type)
                ->mergeSides($row['sides']);

            foreach ($row['sideDetails'] as $sideDetail) {
                $side = $ad->getSideByCode($sideDetail['code']);
                if ($side !== null) {
                    $side->setDescription($sideDetail['description']);
                    $side->setImage($sideDetail['image']);
                    $side->setOneCRef($sideDetail['externalRef'] ?? null);
                    $side->setOneCData($sideDetail);
                }
            }

            if ($row['coordinates'] !== null) {
                $location = $ad->getLocation() ?? new AdvertisementLocation();
                $location
                    ->setLatitude($row['coordinates']['latitude'])
                    ->setLongitude($row['coordinates']['longitude'])
                    ->setAdvertisement($ad);
                $ad->setLocation($location);
                $this->em->persist($location);
            }

            $processed++;
            if ($processed % 100 === 0) {
                $this->em->flush();
            }
        }

        $this->em->flush();
        $io->success(sprintf('Импорт завершён: создано %d, обновлено %d, обработано %d.', $created, $updated, $processed));

        return Command::SUCCESS;
    }

    private function findOrCreateCategory(string $typeName): AdvertisementCategory
    {
        $categoryName = $this->guessCategoryName($typeName);
        $repo = $this->em->getRepository(AdvertisementCategory::class);

        /** @var AdvertisementCategory|null $category */
        $category = $repo->findOneBy(['name' => $categoryName]);
        if ($category !== null) {
            return $category;
        }

        $category = (new AdvertisementCategory())->setName($categoryName);
        $this->em->persist($category);

        return $category;
    }

    private function guessCategoryName(string $typeName): string
    {
        $name = mb_strtolower($typeName);

        return match (true) {
            str_contains($name, 'призматрон') => 'Призматроны',
            str_contains($name, 'экран'), str_contains($name, 'led'), str_contains($name, 'видео') => 'Видеоэкраны',
            str_contains($name, 'бранд') => 'Брандмауэры',
            str_contains($name, 'щит') => 'Щиты',
            default => 'Прочие',
        };
    }
}
