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

use AppBundle\Entity\ICT\Element;
use AppBundle\Entity\ICT\ElementTemplate;
use AppBundle\Entity\ICT\Location;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ElementType extends AbstractType
{
    private $userExtensionService;

    private $entityManager;

    public function __construct(UserExtensionService $userExtensionService, EntityManagerInterface $locationRepository)
    {
        $this->userExtensionService = $userExtensionService;
        $this->entityManager = $locationRepository;
    }

    public function addElements(FormInterface $form)
    {
        $templates = $this->entityManager->getRepository(ElementTemplate::class)->
            findByOrganization($this->userExtensionService->getCurrentOrganization());

        $locations = $this->entityManager->getRepository(Location::class)->
            findRootsByOrganization($this->userExtensionService->getCurrentOrganization());

        $form
            ->add('template', EntityType::class, [
                'label' => 'form.template',
                'class' => ElementTemplate::class,
                'choice_translation_domain' => false,
                'choices' => $templates,
                'placeholder' => 'form.select_template',
                'required' => false
            ])
            ->add('reference', null, [
                'label' => 'form.reference',
                'required' => false
            ])
            ->add('location', EntityType::class, [
                'label' => 'form.location',
                'class' => Location::class,
                'choice_translation_domain' => false,
                'choices' => $locations,
                'placeholder' => 'form.select_location',
                'required' => false
            ])
            ->add('name', null, [
                'label' => 'form.name'
            ])
            ->add('serialNumber', null, [
                'label' => 'form.serial_number'
            ])
            ->add('listedOn', DateType::class, [
                'label' => 'form.listed_on',
                'widget' => 'single_text'
            ])
            ->add('delistedOn', DateType::class, [
                'label' => 'form.delisted_on',
                'required' => false,
                'widget' => 'single_text'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.description',
                'attr' => [
                    'rows' => 8
                ],
                'required' => false
            ])
            ->add('detail', TextareaType::class, [
                'label' => 'form.detail',
                'attr' => [
                    'rows' => 8
                ],
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $this->addElements($form);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $this->addElements($form);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Element::class,
            'translation_domain' => 'ict_element',
            'new' => false
        ]);
    }
}
