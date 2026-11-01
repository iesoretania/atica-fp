<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\WltModule\Meeting;
use App\Form\Type\WltModule\MeetingType;
use App\Security\WltModule\MeetingVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Service\UserExtensionService;
use App\Service\WltModule\MeetingTeacherProvider;
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
 * Formulario de una reunión de FP dual con campos que dependen unos de otros (autor → proyectos; proyecto y fecha →
 * alumnado y docentes). Al cambiar un campo se vuelve a calcular el formulario en el servidor con los valores actuales
 * (sustituye a assets/js/wlt/meeting.js).
 *
 * El guardado NO se hace aquí: el <form> se envía normalmente a MeetingController y el componente solo repinta el
 * formulario mientras se edita. Nunca hace flush. Una reunión nueva aún no existe en la base de datos, así que no se
 * puede hidratar por id: el componente guarda el docente autor (meetingEntity = null) y construye la reunión en cada petición.
 */
#[AsLiveComponent('Wlt:MeetingForm', template: 'components/Wlt/MeetingForm.html.twig')]
final class MeetingForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    /** Reunión existente (null si es nueva). No se llama «meeting» porque es el nombre del formulario (meeting[...]). */
    #[LiveProp]
    public ?Meeting $meetingEntity = null;

    /** Docente autor de una reunión nueva. */
    #[LiveProp]
    public ?Teacher $teacher = null;

    /** Enlace del botón «Volver» (se firma junto al resto de propiedades). */
    #[LiveProp]
    public string $backUrl = '';

    /** Últimos valores de los campos de los que dependen otros: al cambiarlos se vacían los campos dependientes. */
    #[LiveProp]
    public string $lastCreatedBy = '';

    #[LiveProp]
    public string $lastProject = '';

    #[LiveProp]
    public string $lastDate = '';

    public function __construct(
        private readonly UserExtensionService $userExtensionService,
        private readonly MeetingTeacherProvider $teacherProvider,
    ) {
    }

    public function isReadOnly(): bool
    {
        return !$this->isGranted(MeetingVoter::MANAGE, $this->currentMeeting());
    }

    public function getSubmitUrl(): string
    {
        return $this->meetingEntity?->getId() !== null
            ? $this->generateUrl('work_linked_training_meeting_edit', ['id' => $this->meetingEntity->getId()])
            : $this->generateUrl('work_linked_training_meeting_new', ['academicYear' => $this->academicYear()->getId()]);
    }

    private function academicYear(): AcademicYear
    {
        $teacher = $this->meetingEntity?->getCreatedBy() ?? $this->teacher;

        return $teacher instanceof Teacher
            ? $teacher->getAcademicYear()
            : $this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();
    }

    /** Se comprueba en cada petición al componente, igual que hace el controlador de la página en el primer pintado. */
    #[PostHydrate]
    public function authorize(): void
    {
        $organization = $this->userExtensionService->getCurrentOrganization();
        if ($this->meetingEntity === null) {
            $this->denyAccessUnlessGranted(WltOrganizationVoter::WLT_ACCESS_MEETING, $organization);
            // una reunión nueva solo la crea el propio docente, del curso y el centro actuales
            if (
                !$this->teacher instanceof Teacher
                || $this->teacher->getAcademicYear()->getOrganization() !== $organization
                || $this->teacher->getPerson() !== $this->getUser()
            ) {
                throw $this->createAccessDeniedException();
            }
        }
        $this->denyAccessUnlessGranted(MeetingVoter::ACCESS, $this->currentMeeting());
    }

    /**
     * Prepara los valores ANTES de que el formulario se envíe (el trait lo hace con prioridad 0):
     *  - «alumnado» empieza siendo un <select> deshabilitado (valor texto) y pasa a ser casillas (lista) cuando hay
     *    opciones: se convierte en lista (igual que «docentes», que es siempre múltiple).
     *  - Al cambiar el autor se vacían proyecto, alumnado y docentes; al cambiar el proyecto, alumnado y docentes; al
     *    cambiar la fecha, el alumnado. Se quita también su validación para que no aparezca «la opción seleccionada no
     *    es válida».
     */
    #[PreReRender(priority: 10)]
    public function prepareValues(): void
    {
        foreach (['studentEnrollments', 'teachers'] as $field) {
            $value = $this->formValues[$field] ?? [];
            if (!is_array($value)) {
                $this->formValues[$field] = $value === '' || $value === null ? [] : [$value];
            }
        }

        $createdBy = (string) ($this->formValues['createdBy'] ?? '');
        $project = (string) ($this->formValues['project'] ?? '');
        $date = (string) ($this->formValues['dateTime']['date'] ?? '');

        $clear = [];
        if ($createdBy !== $this->lastCreatedBy) {
            $clear = ['project', 'studentEnrollments', 'teachers'];
        } elseif ($project !== $this->lastProject) {
            $clear = ['studentEnrollments', 'teachers'];
        } elseif ($date !== $this->lastDate) {
            $clear = ['studentEnrollments'];
        }
        $this->lastCreatedBy = $createdBy;
        $this->lastProject = $project;
        $this->lastDate = $date;
        if ($clear === []) {
            return;
        }

        foreach ($clear as $field) {
            $this->formValues[$field] = $field === 'project' ? '' : [];
        }
        $paths = array_map(fn (string $field): string => $this->getFormName() . '.' . $field, $clear);
        $this->validatedFields = array_values(array_filter(
            $this->validatedFields,
            static fn (string $path): bool => !in_array($path, $paths, true),
        ));
    }

    #[PostMount(priority: -1)]
    public function rememberValues(): void
    {
        $this->lastCreatedBy = (string) ($this->formValues['createdBy'] ?? '');
        $this->lastProject = (string) ($this->formValues['project'] ?? '');
        $this->lastDate = (string) ($this->formValues['dateTime']['date'] ?? '');
    }

    protected function instantiateForm(): FormInterface
    {
        $meeting = $this->currentMeeting();
        $readOnly = !$this->isGranted(MeetingVoter::MANAGE, $meeting);

        return $this->createForm(MeetingType::class, $meeting, [
            'disabled' => $readOnly,
            'teachers' => $this->teacherProvider->forAcademicYear($this->academicYear(), $readOnly),
        ]);
    }

    private ?Meeting $newMeeting = null;

    private function currentMeeting(): Meeting
    {
        if ($this->meetingEntity !== null) {
            return $this->meetingEntity;
        }

        $this->newMeeting ??= (new Meeting())->setDateTime(new \DateTime());
        if ($this->teacher instanceof Teacher) {
            $this->newMeeting->setCreatedBy($this->teacher);
        }

        return $this->newMeeting;
    }
}
