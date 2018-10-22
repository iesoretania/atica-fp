<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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

namespace AppBundle\Form\Type\ICT;

use AppBundle\Entity\ICT\Priority;
use AppBundle\Entity\Person;
use AppBundle\Form\Model\ICT\TriageTicket;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TriageTicketType extends AbstractType
{
    private $userExtensionService;

    private $entityManager;

    public function __construct(UserExtensionService $userExtensionService, EntityManagerInterface $locationRepository)
    {
        $this->userExtensionService = $userExtensionService;
        $this->entityManager = $locationRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $persons = $this->entityManager->getRepository('AppBundle:Person')->
            findAllByOrganizationSorted($this->userExtensionService->getCurrentOrganization());

        $builder
            ->add('priority', EntityType::class, [
                'label' => 'form.priority',
                'class' => Priority::class,
                'choice_translation_domain' => false,
                'choices' => $this->entityManager->getRepository('AppBundle:ICT\Priority')->
                findAllByOrganizationSortedByPriority($this->userExtensionService->getCurrentOrganization()),
                'placeholder' => 'form.select_priority',
                'required' => true
            ])
            ->add('assignee', EntityType::class, [
                'label' => 'form.assignee',
                'class' => Person::class,
                'choice_translation_domain' => false,
                'choices' => $persons,
                'placeholder' => 'form.no_assignee',
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TriageTicket::class,
            'translation_domain' => 'ict_ticket',
            'new' => false,
            'own' => false
        ]);
    }
}
