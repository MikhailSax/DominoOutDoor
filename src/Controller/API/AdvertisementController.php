<?php

namespace App\Controller\API;

use App\Entity\Advertisement;
use App\Repository\AdvertisementRepository;
use App\Repository\AdvertisementTypeRepository;
use App\Repository\AdvertisementCategoryRepository;
use App\Services\AdvertisementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api', name: 'api_')]
class AdvertisementController extends AbstractController
{
    public function __construct(
        protected AdvertisementService $advertisementService,
    )
    {

    }

    #[Route('/advertisements', name: 'advertisements_list', methods: ['GET'])]
    public function list(Request $request, AdvertisementRepository $repository): JsonResponse
    {
        $ads = $repository->findAll();
        $category = $request->query->get('productType');
        $type = $request->query->get('constrTypeId');

        if (!empty($category) || !empty($type)) {
            $ads = $repository->findByFilters((int)$category, $type);;
            return $this->json($this->advertisementService->getData($ads));
        }
        return $this->json($this->advertisementService->getData($ads));
    }

    #[Route('/advertisements/{id}', name: 'advertisements_show', methods: ['GET'])]
    public function show(Advertisement $advertisement): JsonResponse
    {
        $data = $this->advertisementService->getData([$advertisement]);

        return $this->json($data[0] ?? []);
    }

    #[Route('/filters', name: 'filters', methods: ['GET'])]
    public function filters(
        AdvertisementTypeRepository     $typeRepo,
        AdvertisementCategoryRepository $catRepo,
        Request                         $request,
    ): JsonResponse
    {
        $category_id = $request->query->get('productType');

        if (!empty($category_id)) {
            $constrTypes = $typeRepo->findFilter($category_id);
            return $this->json([
                'productTypes' => array_map(fn($c) => [
                    'id' => $c->getId(),
                    'name' => $c->getName()
                ], $catRepo->findAll()),
                'constrTypes' => array_map(fn($c) => [
                    'id' => $c['id'],
                    'name' => $c['name']

                ], $constrTypes)
            ]);
        }

        return $this->json([
            'productTypes' => array_map(fn($c) => [
                'id' => $c->getId(),
                'name' => $c->getName()
            ], $catRepo->findAll()),

            'constrTypes' => array_map(fn($t) => [
                'id' => $t->getId(),
                'name' => $t->getName()
            ], $typeRepo->findAll()),

            // если районы хардкодим – вернём статикой
        ]);
    }

    private function serializeAdvertisement(Advertisement $ad): array
    {
        return [
            'id' => $ad->getId(),
            'place_number' => $ad->getPlaceNumber(),
            'code' => $ad->getCode(),
            'address' => $ad->getAddress(),
            'sides' => $ad->getSides(),

            'type' => $ad->getType() ? [
                'id' => $ad->getType()->getId(),
                'name' => $ad->getType()->getName(),
                'category' => $ad->getType()->getCategory()?->getName()
            ] : null,

            'location' => $ad->getLocation() ? [
                'latitude' => $ad->getLocation()->getLatitude(),
                'longitude' => $ad->getLocation()->getLongitude(),
                'azimuth' => $ad->getLocation()->getAzimuth()
            ] : null,

            'bookings' => array_map(static fn($booking) => [
                'id' => $booking->getId(),
                'clientName' => $booking->getClientName(),
                'startDate' => $booking->getStartDate()?->format('Y-m-d'),
                'endDate' => $booking->getEndDate()?->format('Y-m-d'),
            ], $ad->getBookings()->toArray()),

            // 🔥 временно генерируем цену (или добавь в БД)
            'price' => random_int(15000, 50000),

            // район можно вычислять/присвоить вручную
            'areaId' => random_int(1, 5)
        ];
    }
}
