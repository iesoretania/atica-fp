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

use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

class OrganizationCommand extends Command
{
    private $translator;

    private $entityManager;

    private $organizationRepository;

    public function __construct(
        TranslatorInterface $translator,
        OrganizationRepository $organizationRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->organizationRepository = $organizationRepository;
    }

    protected function configure()
    {
        $this
            ->setName('app:organization')
            ->setDescription('Create new organization')
            ->addArgument('name', InputArgument::REQUIRED, 'Organization to be created')
            ->addOption('code', null, InputOption::VALUE_REQUIRED, 'Organization code')
            ->addOption('city', null, InputOption::VALUE_REQUIRED, 'Organization city');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        $style->title($this->translator->trans('title.organization', [], 'command'));
        $organizationName = $input->getArgument('name');
        $style->text('Creating new organization "' . $organizationName . '"...');

        $organization = $this->organizationRepository->findOneBy(['name' => $organizationName]);
        if (null === $organization) {
            $organization = new Organization();
            $organization
                ->setName($organizationName)
                ->setCode($input->getOption('code'))
                ->setCity($input->getOption('city'));
            $this->entityManager->persist($organization);
        } else {
            $style->error($this->translator->trans('message.organization.exists', [], 'command'));
            return;
        }

        try {
            $this->entityManager->flush();
            $style->success($this->translator->trans('message.success', [], 'command'));
        } catch (\Exception $e) {
            $style->error($this->translator->trans('message.error', [], 'command'));
        }
    }
}
