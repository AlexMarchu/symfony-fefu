<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Override;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractDashboardController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AdminUrlGenerator $adminUrlGenerator
    ) {
    }

    #[Override]
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'houses_count' => $this->entityManager->getRepository(House::class)->count([]),
            'bookings_count' => $this->entityManager->getRepository(Booking::class)->count([]),
            'users_count' => $this->entityManager->getRepository(User::class)->count([]),
        ]);
    }

    #[Override]
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Symfony FEFU');
    }

    #[Override]
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Панель управления', 'fa fa-home');

        yield MenuItem::section('Каталог');
        yield MenuItem::linkToCrud('Дома', 'fa fa-house', House::class);

        yield MenuItem::section('Бронирования');
        yield MenuItem::linkToCrud('Бронирования', 'fa fa-calendar', Booking::class);

        yield MenuItem::section('Пользователи');
        yield MenuItem::linkToCrud('Пользователи', 'fa fa-users', User::class);

        yield MenuItem::section();
        yield MenuItem::linkToUrl('API Docs', 'fa fa-book', '/api/docs');
        yield MenuItem::linkToUrl('На сайт', 'fa fa-globe', '/');
        yield MenuItem::linkToLogout('Выход', 'fa fa-sign-out');
    }
}
