<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\ItpModule\Activity;
use App\Entity\ItpModule\ProgramGrade;
use App\Form\Type\ItpModule\ActivityType;
use App\Repository\Edu\CriterionRepository;
use App\Repository\Edu\LearningOutcomeRepository;
use App\Repository\Edu\SubjectRepository;
use App\Security\ItpModule\ActivityVoter;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\ItpModule\TrainingProgramVoter;
use App\Service\UserExtensionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Formulario de una actividad formativa de FFEOE con campos en cascada: módulos profesionales → resultados de
 * aprendizaje → criterios de evaluación. Al marcar o desmarcar un campo se recalculan en el servidor las opciones de
 * los siguientes y se conservan solo las selecciones que siguen siendo válidas (sustituye a dependent_fields_controller.js).
 *
 * El guardado NO se hace aquí: el <form> se envía normalmente al controlador y el componente solo repinta el
 * formulario mientras se edita. Nunca hace flush.
 */
#[AsLiveComponent('Itp:ActivityForm', template: 'components/Itp/ActivityForm.html.twig')]
final class ActivityForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    /** Actividad existente (null si es nueva). No se llama «activity» porque es el nombre del formulario (activity[...]). */
    #[LiveProp]
    public ?Activity $activityEntity = null;

    /** Programa del ciclo al que pertenece una actividad nueva. */
    #[LiveProp]
    public ?ProgramGrade $programGrade = null;

    /** Enlace del botón «Volver» (se firma junto al resto de propiedades). */
    #[LiveProp]
    public string $backUrl = '';

    private ?Activity $newActivity = null;

    public function __construct(
        private readonly UserExtensionService $userExtensionService,
        private readonly SubjectRepository $subjectRepository,
        private readonly LearningOutcomeRepository $learningOutcomeRepository,
        private readonly CriterionRepository $criterionRepository,
    ) {
    }

    /** Se comprueba en cada petición al componente, igual que hace el controlador de la página en el primer pintado. */
    #[PostHydrate]
    public function authorize(): void
    {
        $programGrade = $this->currentProgramGrade();
        $organization = $this->userExtensionService->getCurrentOrganization();
        if ($programGrade->getGrade()->getTraining()->getAcademicYear()->getOrganization() !== $organization) {
            throw $this->createAccessDeniedException();
        }
        if ($this->activityEntity === null) {
            $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_MANAGER, $organization);
            $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());
        } else {
            $this->denyAccessUnlessGranted(ActivityVoter::MANAGE, $this->activityEntity);
        }
    }

    public function getSubmitUrl(): string
    {
        return $this->activityEntity?->getId() !== null
            ? $this->generateUrl('in_company_training_phase_activity_edit', ['id' => $this->activityEntity->getId()])
            : $this->generateUrl('in_company_training_phase_activity_new', ['programGrade' => $this->programGrade?->getId()]);
    }

    private function currentProgramGrade(): ProgramGrade
    {
        $programGrade = $this->activityEntity?->getProgramGrade() ?? $this->programGrade;
        if (!$programGrade instanceof ProgramGrade) {
            throw $this->createAccessDeniedException();
        }

        return $programGrade;
    }

    /**
     * Prepara los valores ANTES de que el formulario se envíe: los campos de casillas llegan como lista y solo se
     * conservan los resultados de aprendizaje de los módulos marcados y los criterios de los resultados marcados;
     * así nunca aparece «la opción seleccionada no es válida» al desmarcar un nivel superior.
     */
    #[PreReRender(priority: 10)]
    public function prepareValues(): void
    {
        $ids = static fn (mixed $value): array => array_values(array_unique(array_map(
            'intval',
            array_filter(is_array($value) ? $value : [$value], static fn (mixed $v): bool => is_scalar($v) && ctype_digit((string) $v))
        )));

        $academicYear = $this->currentProgramGrade()->getGrade()->getTraining()->getAcademicYear();
        $subjectIds = $ids($this->formValues['subjects'] ?? []);
        $subjects = $subjectIds === [] ? [] : $this->subjectRepository->findAllInListByIdAndAcademicYear($subjectIds, $academicYear);

        $outcomeIds = $ids($this->formValues['learningOutcomes'] ?? []);
        $outcomes = ($subjects === [] || $outcomeIds === []) ? [] : $this->learningOutcomeRepository->findAllInListByIdAndSubjects($outcomeIds, $subjects);

        $criterionIds = $ids($this->formValues['criteria'] ?? []);
        $criteria = ($outcomes === [] || $criterionIds === []) ? [] : array_filter(
            $this->criterionRepository->findByLearningOutcomes($outcomes),
            static fn ($criterion): bool => in_array($criterion->getId(), $criterionIds, true)
        );

        $this->formValues['subjects'] = array_map(static fn ($s): string => (string) $s->getId(), $subjects);
        $this->formValues['learningOutcomes'] = array_map(static fn ($o): string => (string) $o->getId(), $outcomes);
        $this->formValues['criteria'] = array_values(array_map(static fn ($c): string => (string) $c->getId(), $criteria));
    }

    protected function instantiateForm(): FormInterface
    {
        $activity = $this->currentActivity();

        return $this->createForm(ActivityType::class, $activity, ['program_grade' => $activity->getProgramGrade()]);
    }

    private function currentActivity(): Activity
    {
        if ($this->activityEntity !== null) {
            return $this->activityEntity;
        }

        return $this->newActivity ??= (new Activity())->setProgramGrade($this->programGrade);
    }
}
