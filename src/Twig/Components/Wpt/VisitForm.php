<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

use App\Entity\Edu\Teacher;
use App\Entity\WptModule\Contact;
use App\Form\Type\WptModule\VisitType;
use App\Security\WptModule\OrganizationVoter as WptOrganizationVoter;
use App\Security\WptModule\VisitVoter;
use App\Service\UserExtensionService;
use App\Service\WptModule\VisitTeacherProvider;
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
 * Formulario de una visita de FCT con campos que dependen unos de otros (docente → centro de trabajo → acuerdos →
 * alumnado). Al cambiar un campo se vuelve a calcular el formulario en el servidor con los valores actuales, así que no
 * hay JavaScript a medida (sustituye a dependent_fields_controller.js en esta pantalla).
 *
 * El guardado NO se hace aquí: el <form> se envía normalmente a VisitController (redirecciones, mensajes, etc.), y
 * el componente solo repinta el formulario mientras se edita. Nunca hace flush.
 *
 * Una visita nueva aún no existe en la base de datos, de modo que no se puede hidratar por id: el componente guarda el
 * docente (contact = null, teacher = X) y los valores del formulario (formValues), y construye la visita en cada petición.
 */
#[AsLiveComponent('Wpt:VisitForm', template: 'components/Wpt/VisitForm.html.twig')]
final class VisitForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    /** Visita existente (null si es nueva). No se llama «visit» porque es el nombre del formulario (visit[...]). */
    #[LiveProp]
    public ?Contact $contact = null;

    /** Docente de una visita nueva. */
    #[LiveProp]
    public ?Teacher $teacher = null;

    /** Enlace del botón «Volver» (se firma junto al resto de propiedades, el cliente no puede cambiarlo). */
    #[LiveProp]
    public string $backUrl = '';

    /** Último docente seleccionado: al cambiarlo se elige automáticamente el primer centro de trabajo. */
    #[LiveProp]
    public string $lastTeacher = '';

    public function __construct(
        private readonly UserExtensionService $userExtensionService,
        private readonly VisitTeacherProvider $teacherProvider,
    ) {
    }

    public function isReadOnly(): bool
    {
        return !$this->isGranted(VisitVoter::MANAGE, $this->currentVisit());
    }

    public function getSubmitUrl(): string
    {
        return $this->contact?->getId() !== null
            ? $this->generateUrl('workplace_training_visit_edit', ['id' => $this->contact->getId()])
            : $this->generateUrl('workplace_training_visit_new', ['id' => $this->teacher?->getId()]);
    }

    /**
     * Se comprueba en cada petición al componente (cada actualización llega a /_components/... como petición nueva),
     * igual que hace el controlador de la página, que es quien autoriza el primer pintado.
     */
    #[PostHydrate]
    public function authorize(): void
    {
        $organization = $this->userExtensionService->getCurrentOrganization();
        if ($this->contact === null) {
            $this->denyAccessUnlessGranted(WptOrganizationVoter::WPT_CREATE_VISIT, $organization);
            if (!$this->teacher instanceof Teacher || $this->teacher->getAcademicYear()->getOrganization() !== $organization) {
                throw $this->createAccessDeniedException();
            }
        }
        $this->denyAccessUnlessGranted(VisitVoter::ACCESS, $this->currentVisit());
    }

    /**
     * Prepara los valores ANTES de que el formulario se envíe (el trait lo hace con prioridad 0):
     *  - «acuerdos» y «alumnado» empiezan siendo un <select> deshabilitado (valor texto) y pasan a ser casillas (lista)
     *    cuando hay opciones; el navegador guarda el primer valor como texto, así que se convierte en lista.
     *  - Si se ha cambiado el docente, el centro de trabajo, los acuerdos y el alumnado elegidos ya no valen: se vacían
     *    (y se quita su validación) para que no aparezca «la opción seleccionada no es válida».
     */
    #[PreReRender(priority: 10)]
    public function prepareValues(): void
    {
        foreach (['agreements', 'studentEnrollments'] as $field) {
            $value = $this->formValues[$field] ?? [];
            if (!is_array($value)) {
                $this->formValues[$field] = $value === '' || $value === null ? [] : [$value];
            }
        }

        $teacher = (string) ($this->formValues['teacher'] ?? '');
        if ($teacher === $this->lastTeacher) {
            return;
        }
        $this->lastTeacher = $teacher;
        $this->teacherChanged = true;

        $dependent = ['workcenter', 'agreements', 'studentEnrollments'];
        $this->formValues['workcenter'] = '';
        $this->formValues['agreements'] = [];
        $this->formValues['studentEnrollments'] = [];
        $paths = array_map(fn (string $field): string => $this->getFormName() . '.' . $field, $dependent);
        $this->validatedFields = array_values(array_filter(
            $this->validatedFields,
            static fn (string $path): bool => !in_array($path, $paths, true),
        ));
    }

    #[PostMount(priority: -1)]
    public function rememberTeacher(): void
    {
        $this->lastTeacher = (string) ($this->formValues['teacher'] ?? '');
    }

    /**
     * Tras cambiar el docente se elige el primer centro de trabajo que le corresponda y se recalcula el resto (antes lo
     * hacía el JavaScript). Solo cuando cambia el docente, para respetar que se deje en blanco a propósito.
     */
    #[PreReRender(priority: -10)]
    public function selectFirstWorkcenterWhenTeacherChanges(): void
    {
        if (!$this->teacherChanged || ($this->formValues['teacher'] ?? '') === '') {
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
        $visit = $this->currentVisit();

        return $this->createForm(VisitType::class, $visit, [
            'disabled' => !$this->isGranted(VisitVoter::MANAGE, $visit),
            'teachers' => $this->teachers($visit),
        ]);
    }

    private ?Contact $newVisit = null;

    /** Se ha cambiado el docente en esta petición. */
    private bool $teacherChanged = false;

    private function currentVisit(): Contact
    {
        if ($this->contact !== null) {
            return $this->contact;
        }

        return $this->newVisit ??= (new Contact())->setTeacher($this->teacher)->setDateTime(new \DateTime());
    }

    /** @return list<Teacher> */
    private function teachers(Contact $visit): array
    {
        $organization = $this->userExtensionService->getCurrentOrganization();
        $academicYear = $visit->getTeacher() instanceof Teacher
            ? $visit->getTeacher()->getAcademicYear()
            : $organization->getCurrentAcademicYear();

        $teachers = $this->teacherProvider->forAcademicYear($academicYear, !$this->isGranted(VisitVoter::MANAGE, $visit));
        // quien puede modificar una visita ajena (la jefatura, por ejemplo) debe ver al docente que la registró
        if ($visit->getTeacher() instanceof Teacher && !in_array($visit->getTeacher(), $teachers, true)) {
            $teachers[] = $visit->getTeacher();
        }

        return $teachers;
    }
}
