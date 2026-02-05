<?php

namespace App\Controller\Admin;

use App\Entity\Advertisement;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AdvertisementCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Advertisement::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('code', 'Код'),
            TextField::new('placeNumber', 'Номер места'),
            TextField::new('address', 'Адрес'),
            TextField::new('sides', 'Стороны (через запятую)')
                ->formatValue(static function ($value, Advertisement $entity) {
                    return implode(', ', $entity->getSides());
                })
                ->onlyOnIndex(),
            AssociationField::new('type', 'Тип конструкции'),
        ];
    }
}
