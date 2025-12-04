<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Создает нового пользователя с ролью ROLE_ADMIN'
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private UserService $userService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('phone', InputArgument::REQUIRED)
            ->addArgument('name', InputArgument::REQUIRED)
            ->addArgument('password', InputArgument::REQUIRED);
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $phone = $input->getArgument('phone');
        $name = $input->getArgument('name');
        $password = $input->getArgument('password');

        if (empty($phone)) {
            $io->error('Phone number cannot be empty!');
            return Command::FAILURE;
        }

        if (empty($name)) {
            $io->error('Name cannot be empty!');
            return Command::FAILURE;
        }

        if (empty($password)) {
            $io->error('Password cannot be empty!');
            return Command::FAILURE;
        }

        try {
            $existingUser = $this->userRepository->findByPhone($phone);

            if ($existingUser) {
                if (!$existingUser->isAdmin()) {
                    $existingUser->promoteToAdmin();
                    $io->success(sprintf(
                        'User %s (%s) has been granted the admin role.',
                        $existingUser->getName(),
                        $phone
                    ));
                } else {
                    $io->note(message: sprintf(
                        'User %s (%s) is already an admin.',
                        $existingUser->getName(),
                        $phone
                    ));
                }

                if (!$this->userService->isPasswordValid($existingUser, $password)) {
                    $hashedPassword = $this->userService
                        ->getPasswordHasher()
                        ->hashPassword($existingUser, $password);
                    $existingUser->setPassword($hashedPassword);
                    $io->note('Password updated');
                }
            } else {
                $user = $this->userService->create(
                    $name,
                    $phone,
                    $password,
                    [User::ROLE_USER, User::ROLE_ADMIN]
                );

                $io->success(sprintf(
                    'Created new admin: %s (%s)',
                    $user->getName(),
                    $phone
                ));
            }

            $this->entityManager->flush();

            $io->table(
                ['Field', 'Value'],
                [
                    ['Name', $name],
                    ['Phone', $phone],
                    ['Roles', implode(', ', [User::ROLE_USER, User::ROLE_ADMIN])],
                    ['Status', 'Admin created/updated successfully!'],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Error while creating admin: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
