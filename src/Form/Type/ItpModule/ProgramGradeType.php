<?php

namespace App\Form\Type\ItpModule;

use App\Entity\ItpModule\ProgramGrade;
use App\Repository\ItpModule\ProgramGradeLearningOutcomeRepository;
use App\Repository\ItpModule\ProgramGradeRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class ProgramGradeType extends AbstractType
{
    public function __construct(private readonly ProgramGradeLearningOutcomeRepository $programGradeLearningOutcomeRepository, private readonly ProgramGradeRepository $programGradeRepository)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('targetHours', MoneyType::class, [
                'label' => 'form.target_hours',
                'currency' => false,
                'divisor' => 100,
                'constraints' => [
                    new GreaterThanOrEqual(0)
                ],
                'required' => false
            ])
            ->add('subjects', null, [
                'label' => 'form.subjects',
                'choices' => $options['subjects'],
                'choice_label' => 'name',
                'expanded' => true,
                'multiple' => true,
                'required' => true
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();
            $data = $event->getData();

            $form
                ->add('currentProgramGradeLearningOutcomes', CollectionType::class, [
                    'mapped' => false,
                    'label' => false,
                    'entry_type' => CustomProgramGradeLearningOutcomeType::class,
                    'required' => true
                ]);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData();

            $subjects = $data['subjects'] ?? [];
            $oldFormData = $form->get('currentProgramGradeLearningOutcomes')->getData();
            $choicesFormData = $this->programGradeLearningOutcomeRepository->generateByProgramGradeAndSubjects($event->getForm()->getData(), $subjects);
            if (!isset($data['currentProgramGradeLearningOutcomes'])) {
                $data['currentProgramGradeLearningOutcomes'] = [];
            }
            foreach ($data['currentProgramGradeLearningOutcomes'] as $key => $datum) {
                if (!isset($choicesFormData[$key])) {
                    unset($data['currentProgramGradeLearningOutcomes'][$key]);
                }
            }
            foreach ($choicesFormData as $key => $choice) {
                if (!isset($data['currentProgramGradeLearningOutcomes'][$key])) {
                    $data['currentProgramGradeLearningOutcomes'][$key] = ['selected' => (string) $choice->getSelected()];
                }
            }
            $newFormData = [];
            foreach ($choicesFormData as $key => $choice) {
                if (!isset($oldFormData[$key])) {
                    if (!isset($data['currentProgramGradeLearningOutcomes'][$key])) {
                        $data['currentProgramGradeLearningOutcomes'][$key] = ['selected' => (string) $choice->getSelected()];
                    }
                    $newFormData[$key] = $choice;
                } else {
                    $newFormData[$key] = $oldFormData[$key];
                }
            }
            $event->setData($data);
            $form->get('currentProgramGradeLearningOutcomes')->setData($newFormData);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProgramGrade::class,
            'subjects' => [],
            'translation_domain' => 'itp_grade'
        ]);
    }

}
