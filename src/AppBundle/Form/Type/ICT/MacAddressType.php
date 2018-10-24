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

use AppBundle\Entity\ICT\Location;
use AppBundle\Entity\ICT\MacAddress;
use AppBundle\Entity\ICT\Priority;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class MacAddressType extends AbstractType
{
    private $userExtensionService;
    private $entityManager;
    private $translatorInterface;

    public function __construct(
        UserExtensionService $userExtensionService,
        EntityManagerInterface $locationRepository,
        TranslatorInterface $translatorInterface
    ) {
        $this->userExtensionService = $userExtensionService;
        $this->entityManager = $locationRepository;
        $this->translatorInterface = $translatorInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $persons = $this->entityManager->getRepository('AppBundle:Person')->
            findAllByOrganizationSorted($this->userExtensionService->getCurrentOrganization());

        $builder
            ->add('person', null, [
                'label' => 'form.person',
                'choices' => $persons,
                'disabled' => (false === $this->userExtensionService->isUserLocalAdministrator())
            ])
            ->add('address', TextType::class, [
                'label' => 'form.address',
                'required' => false,
                'attr' => [
                    'placeholder' => 'form.address.placeholder'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.description',
                'required' => true,
                'attr' => [
                    'placeholder' => 'form.description.placeholder'
                ]
            ]);

        $admin = $options['admin'];

        if ($options['new'] === false) {
            $builder
                ->add('createdOn', null, [
                    'label' => 'form.registered_on',
                    'widget' => 'single_text',
                    'required' => true,
                    'disabled' => false === $admin
                ])
                ->add('registeredOn', null, [
                    'label' => 'form.registered_on',
                    'widget' => 'single_text',
                    'required' => false,
                    'disabled' => false === $admin
                ])
                ->add('unRegisteredOn', null, [
                    'label' => 'form.unregistered_on',
                    'widget' => 'single_text',
                    'required' => false,
                    'disabled' => false === $admin
                ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MacAddress::class,
            'translation_domain' => 'ict_mac_address',
            'new' => false,
            'admin' => false
        ]);
    }
}
