<?php

namespace App\Controller\Admin;

use App\Entity\Banner;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;


#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {

        $routeBuilder = $this->container->get(AdminUrlGenerator::class);
        $url = $routeBuilder->setController(UserCrudController::class)->generateUrl();
        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Domino');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute('Вернутся на сайт', 'fas fa-home', 'app_homepage');
        yield MenuItem::linkToCrud('Пользователи', 'fas fa-user', User::class);
        yield MenuItem::linkToCrud('Баннеры', 'fas fa-user', Banner::class);
    }
}
