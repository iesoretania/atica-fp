<?php
/*
  Copyright (C) 2018-2020: Luis Ramón López López

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see [http://www.gnu.org/licenses/].
*/

namespace App\Command;

use App\Entity\Person;
use App\Repository\PersonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminCommand extends Command
{
    private $translator;

    private $userPasswordEncoder;

    private $entityManager;

    private $personRepository;

    public function __construct(
        TranslatorInterface $translator,
        UserPasswordEncoderInterface $userPasswordEncoder,
        PersonRepository $personRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->translator = $translator;
        $this->userPasswordEncoder = $userPasswordEncoder;
        $this->entityManager = $entityManager;
        $this->personRepository = $personRepository;
    }

    protected function configure()
    {
        $this
            ->setName('app:admin')
            ->setDescription('Create admin user')
            ->addArgument('username', InputArgument::REQUIRED, 'Username to be created or updated')
            ->addOption('firstname', null, InputOption::VALUE_OPTIONAL, 'First name to be assigned when creating user (optional)')
            ->addOption('lastname', null, InputOption::VALUE_OPTIONAL, 'Last name to be assigned when creating user (optional)')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Password to be asigned (if not specified, will ask for one');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        $style->title($this->translator->trans('title.admin', [], 'command'));
        $username = $input->getArgument('username');
        $style->text('Creating user "' . $username . '"...');
        $password = $input->getOption('password') ?: $style->askHidden('Enter password: ');

        $user = $this->personRepository->findOneBy(['loginUsername' => $username]);
        if (null === $user) {
            $user = new Person();
            $user
                ->setLoginUsername($username)
                ->setFirstName($input->getOption('firstname') ?: ucwords($username))
                ->setLastName($input->getOption('lastname') ?: ucwords($username));
            $this->entityManager->persist($user);
        } else {
            $style->warning($this->translator->trans('message.admin.updating', [], 'command'));
        }
        $user
            ->setPassword($this->userPasswordEncoder->encodePassword($user, $password))
            ->setEnabled(true)
            ->setGlobalAdministrator(true)
            ->setForcePasswordChange(true)
            ->setExternalCheck(false);

        try {
            $this->entityManager->flush();
            $style->success($this->translator->trans('message.success', [], 'command'));
        } catch (\Exception $e) {
            $style->error($this->translator->trans('message.error', [], 'command'));
        }
    }
}
