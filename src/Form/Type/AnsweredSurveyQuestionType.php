<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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

namespace App\Form\Type;

use App\Entity\AnsweredSurveyQuestion;
use App\Entity\SurveyQuestion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class AnsweredSurveyQuestionType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            /** @var AnsweredSurveyQuestion $data */
            $data = $event->getData();

            $type = $data->getSurveyQuestion()->getType();
            switch ($type) {
                case SurveyQuestion::TEXTAREA:
                    $form
                        ->add('textValue', TextareaType::class, [
                            'label' => false,
                            'attr' => ['rows' => 10],
                            'required' => $data->getSurveyQuestion()->isMandatory()
                        ]);
                    break;
                case SurveyQuestion::TEXTFIELD:
                    $form
                        ->add('textValue', TextType::class, [
                            'label' => false,
                            'required' => $data->getSurveyQuestion()->isMandatory()
                        ]);
                    break;
                case SurveyQuestion::RANGE_0_5:
                case SurveyQuestion::RANGE_1_5:
                case SurveyQuestion::RANGE_1_10:
                    if ($type === SurveyQuestion::RANGE_1_5) {
                        $min = 1;
                        $max = 5;
                    } elseif ($type === SurveyQuestion::RANGE_1_10) {
                        $min = 1;
                        $max = 10;
                    } elseif ($type === SurveyQuestion::RANGE_0_5) {
                        $min = 0;
                        $max = 5;
                    } else {
                        // RANGE_0_10
                        $min = 0;
                        $max = 10;
                    }
                $itemCount = $type === SurveyQuestion::RANGE_1_5 ? 5 : 10;
                    $items = explode(';', $data->getSurveyQuestion()->getItems());

                    $choices = [];
                    $count = 0;
                    for ($i = $min; $i <= $max; $i++) {
                        if (isset($items[$count]) && $items[$count] !== '') {
                            $choices[trim($items[$count])] = $i;
                        } else {
                            $choices[$i] = $i;
                        }
                        $count++;
                    }

                    $form
                        ->add('numericValue', ChoiceType::class, [
                            'label' => false,
                            'label_attr' => ['class' => 'radio-inline'],
                            'choices' => $choices,
                            'placeholder' => $this->translator->trans(
                                'form.no_response',
                                [],
                                'wlt_survey'
                            ),
                            'expanded' => true,
                            'required' => $data->getSurveyQuestion()->isMandatory()
                        ]);
                    break;
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AnsweredSurveyQuestion::class,
            'translation_domain' => false
        ]);
    }
}
