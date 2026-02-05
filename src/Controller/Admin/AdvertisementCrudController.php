<?php

namespace App\Controller\Admin;

use App\Entity\Advertisement;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AdvertisementCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Advertisement::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Рекламная конструкция')
            ->setEntityLabelInPlural('Рекламные конструкции')
            ->setPageTitle(Crud::PAGE_INDEX, 'Рекламные конструкции')
            ->setPageTitle(Crud::PAGE_NEW, 'Добавление рекламной конструкции')
            ->setPageTitle(Crud::PAGE_EDIT, 'Редактирование рекламной конструкции')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Карточка рекламной конструкции');
    }

    public function configureFields(string $pageName): iterable
    {
        $sideAImagePreview = ImageField::new('sideAImage', 'Фото стороны А')
            ->setBasePath('/uploads/advertisements/')
            ->hideOnForm();

        $sideAImageUpload = ImageField::new('sideAImage', 'Загрузить фото стороны А')
            ->setBasePath('/uploads/advertisements/')
            ->setUploadDir('public/uploads/advertisements/')
            ->setUploadedFileNamePattern('side-a-[randomhash].[extension]')
            ->setRequired(false)
            ->setFormTypeOption('attr.accept', 'image/*')
            ->onlyOnForms();

        $sideBImagePreview = ImageField::new('sideBImage', 'Фото стороны Б')
            ->setBasePath('/uploads/advertisements/')
            ->hideOnForm();

        $sideBImageUpload = ImageField::new('sideBImage', 'Загрузить фото стороны Б')
            ->setBasePath('/uploads/advertisements/')
            ->setUploadDir('public/uploads/advertisements/')
            ->setUploadedFileNamePattern('side-b-[randomhash].[extension]')
            ->setRequired(false)
            ->setFormTypeOption('attr.accept', 'image/*')
            ->onlyOnForms();

        return [
            IdField::new('id', 'ID')->hideOnForm(),
            TextField::new('code', 'Код'),
            TextField::new('placeNumber', 'Номер места'),
            TextareaField::new('address', 'Адрес'),
            TextField::new('sides', 'Стороны (через запятую)')
                ->formatValue(static function ($value, Advertisement $entity) {
                    return implode(', ', $entity->getSides());
                })
                ->onlyOnIndex(),

            AssociationField::new('type', 'Категория (тип рекламной продукции)'),
            TextField::new('categoryName', 'Категория рекламы')->onlyOnIndex(),
            NumberField::new('latitude', 'Широта')->setNumDecimals(6),
            NumberField::new('longitude', 'Долгота')->setNumDecimals(6),
            TextareaField::new('sideADescription', 'Описание стороны А')->hideOnIndex(),
            MoneyField::new('sideAPrice', 'Цена стороны А')->setCurrency('RUB')->setStoredAsCents(false),
          
            $sideAImagePreview,
            $sideAImageUpload,
            TextareaField::new('sideBDescription', 'Описание стороны Б')->hideOnIndex(),
            MoneyField::new('sideBPrice', 'Цена стороны Б')->setCurrency('RUB')->setStoredAsCents(false),
            $sideBImagePreview,
            $sideBImageUpload,

            ImageField::new('sideAImage', 'Изображение стороны А')
                ->setBasePath('/uploads/advertisements/')
                ->setUploadDir('public/uploads/advertisements/')
                ->setUploadedFileNamePattern('side-a-[slug]-[timestamp].[extension]')
                ->setRequired(false),
            TextareaField::new('sideBDescription', 'Описание стороны Б')->hideOnIndex(),
            MoneyField::new('sideBPrice', 'Цена стороны Б')->setCurrency('RUB')->setStoredAsCents(false),
            ImageField::new('sideBImage', 'Изображение стороны Б')
                ->setBasePath('/uploads/advertisements/')
                ->setUploadDir('public/uploads/advertisements/')
                ->setUploadedFileNamePattern('side-b-[slug]-[timestamp].[extension]')
                ->setRequired(false),

            TextareaField::new('sideBDescription', 'Описание стороны Б')->hideOnIndex(),
            MoneyField::new('sideBPrice', 'Цена стороны Б')->setCurrency('RUB')->setStoredAsCents(false),
        ];
    }
}
