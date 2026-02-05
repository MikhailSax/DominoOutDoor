<?php

namespace App\Controller\Admin;

use App\Entity\AdvertisementBooking;
use App\Repository\AdvertisementBookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AdvertisementBookingCrudController extends AbstractCrudController
{
    public function __construct(private readonly AdvertisementBookingRepository $bookingRepository)
    {
    }

    public static function getEntityFqcn(): string
    {
        return AdvertisementBooking::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('advertisement', 'Конструкция'),
            TextField::new('clientName', 'Клиент'),
            DateField::new('startDate', 'Дата начала'),
            DateField::new('endDate', 'Дата окончания'),
            TextareaField::new('comment', 'Комментарий')->hideOnIndex(),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->validateBooking($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->validateBooking($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function validateBooking(mixed $entityInstance): void
    {
        if (!$entityInstance instanceof AdvertisementBooking) {
            return;
        }

        if ($entityInstance->getAdvertisement() === null || $entityInstance->getStartDate() === null || $entityInstance->getEndDate() === null) {
            throw new \InvalidArgumentException('Заполните конструкцию и обе даты бронирования.');
        }

        if ($entityInstance->getEndDate() < $entityInstance->getStartDate()) {
            throw new \InvalidArgumentException('Дата окончания не может быть раньше даты начала.');
        }

        if ($this->bookingRepository->hasOverlap(
            $entityInstance->getAdvertisement(),
            $entityInstance->getStartDate(),
            $entityInstance->getEndDate(),
            $entityInstance->getId(),
        )) {
            throw new \InvalidArgumentException('Этот период уже занят для выбранной рекламной конструкции.');
        }
    }
}
