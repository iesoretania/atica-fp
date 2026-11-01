<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\StudentProgram;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Form\Type\ItpModule\StudentProgramWorkcenterBatchType;
use App\Form\Type\ItpModule\StudentProgramWorkcenterType;
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
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * Formulario de una estancia de FFEOE (individual, nueva o existente, o por lote para varios estudiantes de un grupo)
 * con campos que dependen unos de otros: al elegir la empresa se recalculan sus centros de trabajo (se elige el
 * primero) y las actividades formativas que ofrece (sustituye a dependent_fields_controller.js en estas pantallas).
 *
 * El guardado NO se hace aquí: el <form> se envía normalmente al controlador y el componente solo repinta el
 * formulario mientras se edita. Nunca hace flush.
 *
 * Modos (una sola de estas propiedades identifica la estancia):
 *  - stay: estancia existente;
 *  - studentProgram: estancia nueva de un estudiante;
 *  - programGroup: alta por lote para el alumnado de un grupo.
 */
#[AsLiveComponent('Itp:StayForm', template: 'components/Itp/StayForm.html.twig')]
final class StayForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?StudentProgramWorkcenter $stay = null;

    #[LiveProp]
    public ?StudentProgram $studentProgram = null;

    #[LiveProp]
    public ?ProgramGroup $programGroup = null;

    /** Enlace del botón «Volver» (se firma junto al resto de propiedades). */
    #[LiveProp]
    public string $backUrl = '';

    /** true si se edita desde la gestión del ciclo (otra ruta de edición y de borrado). */
    #[LiveProp]
    public bool $manage = false;

    /** Última empresa elegida: al cambiarla se vacían el centro de trabajo y las actividades. */
    #[LiveProp]
    public string $lastCompany = '';

    private ?StudentProgramWorkcenter $newStay = null;

    private bool $companyChanged = false;

    public function __construct(private readonly UserExtensionService $userExtensionService)
    {
    }

    public function isBatch(): bool
    {
        return $this->stay === null && $this->studentProgram === null && $this->programGroup !== null;
    }

    public function getSubmitUrl(): string
    {
        if ($this->stay?->getId() !== null) {
            return $this->generateUrl(
                $this->manage ?'in_company_training_phase_student_program_workcenter_manage_edit' : 'in_company_training_phase_student_program_workcenter_edit',
                ['studentProgramWorkcenter' => $this->stay->getId()]
            );
        }
        if ($this->isBatch()) {
            return $this->generateUrl('in_company_training_phase_student_program_workcenter_manage_new', ['programGroup' => $this->programGroup->getId()]);
        }

        return $this->generateUrl('in_company_training_phase_student_program_workcenter_new', ['studentProgram' => $this->studentProgram?->getId()]);
    }

    /** Se comprueba en cada petición al componente, igual que hace el controlador de la página en el primer pintado. */
    #[PostHydrate]
    public function authorize(): void
    {
        $programGrade = $this->programGrade();
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());
        if ($programGrade->getGrade()->getTraining()->getAcademicYear()->getOrganization() !== $this->userExtensionService->getCurrentOrganization()) {
            throw $this->createAccessDeniedException();
        }
    }

    private function programGrade(): ProgramGrade
    {
        $programGroup = $this->programGroup ?? $this->stay?->getStudentProgram()?->getProgramGroup() ?? $this->studentProgram?->getProgramGroup();
        if (!$programGroup instanceof ProgramGroup) {
            throw $this->createAccessDeniedException();
        }

        return $programGroup->getProgramGrade();
    }

    /**
     * Prepara los valores ANTES de que el formulario se envíe: las actividades (y el alumnado en el alta por lote)
     * empiezan siendo texto vacío y pasan a ser lista de casillas; y al cambiar la empresa se vacían el centro de
     * trabajo y las actividades (y se quita su validación para que no aparezca «la opción seleccionada no es válida»).
     */
    #[PreReRender(priority: 10)]
    public function prepareValues(): void
    {
        foreach (['selectedActivities', 'students'] as $field) {
            $value = $this->formValues[$field] ?? [];
            if (!is_array($value)) {
                $this->formValues[$field] = $value === '' || $value === null ? [] : [$value];
            }
        }

        $company = (string) ($this->formValues['company'] ?? '');
        if ($company === $this->lastCompany) {
            return;
        }
        $this->lastCompany = $company;
        $this->companyChanged = true;

        $this->formValues['workcenter'] = '';
        $this->formValues['selectedActivities'] = [];
        $paths = array_map(fn (string $field): string => $this->getFormName() . '.' . $field, ['workcenter', 'selectedActivities']);
        $this->validatedFields = array_values(array_filter(
            $this->validatedFields,
            static fn (string $path): bool => !in_array($path, $paths, true),
        ));
    }

    #[PostMount(priority: -1)]
    public function rememberCompany(): void
    {
        $this->lastCompany = (string) ($this->formValues['company'] ?? '');
    }

    /** Tras cambiar la empresa se elige su primer centro de trabajo (antes lo hacía el JavaScript). */
    #[PreReRender(priority: -10)]
    public function selectFirstWorkcenterWhenCompanyChanges(): void
    {
        if (!$this->companyChanged || ($this->formValues['company'] ?? '') === '') {
            return;
        }
        foreach ($this->getFormView()->children['workcenter']->vars['choices'] as $choice) {
            $this->formValues['workcenter'] = (string) $choice->value;
            $this->submitForm(false);
            break;
        }
    }

    protected function instantiateForm(): FormInterface
    {
        if ($this->isBatch()) {
            return $this->createForm(StudentProgramWorkcenterBatchType::class, $this->currentStay(), ['program_group' => $this->programGroup]);
        }

        return $this->createForm(StudentProgramWorkcenterType::class, $this->currentStay());
    }

    private function currentStay(): StudentProgramWorkcenter
    {
        if ($this->stay !== null) {
            return $this->stay;
        }

        $this->newStay ??= new StudentProgramWorkcenter();
        if ($this->studentProgram instanceof StudentProgram) {
            $this->newStay->setStudentProgram($this->studentProgram);
        }

        return $this->newStay;
    }
}
