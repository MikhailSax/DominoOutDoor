<?php

namespace App\Controller\Admin;

use App\Entity\Advertisement;
use App\Entity\AdvertisementBooking;
use App\Entity\Banner;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        $routeBuilder = $this->container->get(AdminUrlGenerator::class);
        $url = $routeBuilder->setController(AdvertisementBookingCrudController::class)->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Domino');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute('Вернуться на сайт', 'fas fa-home', 'app_homepage');
        yield MenuItem::linkToCrud('Пользователи', 'fas fa-user', User::class);
        yield MenuItem::linkToCrud('Баннеры', 'fas fa-image', Banner::class);
        yield MenuItem::linkToCrud('Рекламные конструкции', 'fas fa-map-marker-alt', Advertisement::class);
        yield MenuItem::linkToCrud('Бронирования', 'fas fa-calendar-check', AdvertisementBooking::class);
        yield MenuItem::linkToRoute('Календарь занятости', 'fas fa-calendar', 'admin_booking_calendar');
    }
}
