<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\Contact;
use App\Form\Type\ItpModule\ContactType;
use App\Security\ItpModule\ContactVoter;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Service\ItpModule\ContactTeacherProvider;
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
 * Formulario de un contacto de FFEOE con campos que dependen unos de otros (fecha y centro de trabajo → planes de
 * formación → alumnado). Al cambiar un campo se vuelve a calcular el formulario en el servidor con los valores
 * actuales (sustituye a dependent_fields_controller.js en esta pantalla).
 *
 * El guardado NO se hace aquí: el <form> se envía normalmente a ContactController y el componente solo repinta el
 * formulario mientras se edita. Nunca hace flush. Un contacto nuevo aún no existe en la base de datos, así que no se
 * puede hidratar por id: el componente guarda el docente (contactEntity = null) y construye el contacto en cada petición.
 */
#[AsLiveComponent('Itp:ContactForm', template: 'components/Itp/ContactForm.html.twig')]
final class ContactForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    /** Contacto existente (null si es nuevo). No se llama «contact» porque es el nombre del formulario (contact[...]). */
    #[LiveProp]
    public ?Contact $contactEntity = null;

    /** Docente de un contacto nuevo. */
    #[LiveProp]
    public ?Teacher $teacher = null;

    /** Enlace del botón «Volver» (se firma junto al resto de propiedades). */
    #[LiveProp]
    public string $backUrl = '';

    /** Últimos valores de los campos de los que dependen otros: al cambiarlos se vacían los campos dependientes. */
    #[LiveProp]
    public string $lastWorkcenter = '';

    #[LiveProp]
    public string $lastDependencies = '';

    public function __construct(
        private readonly UserExtensionService $userExtensionService,
        private readonly ContactTeacherProvider $teacherProvider,
    ) {
    }

    public function isReadOnly(): bool
    {
        return !$this->isGranted(ContactVoter::MANAGE, $this->currentContact());
    }

    public function getSubmitUrl(): string
    {
        return $this->contactEntity?->getId() !== null
            ? $this->generateUrl('in_company_training_phase_contact_edit', ['id' => $this->contactEntity->getId()])
            : $this->generateUrl('in_company_training_phase_contact_new');
    }

    /** Se comprueba en cada petición al componente, igual que hace el controlador de la página en el primer pintado. */
    #[PostHydrate]
    public function authorize(): void
    {
        $organization = $this->userExtensionService->getCurrentOrganization();
        if ($this->contactEntity === null) {
            $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_CREATE_VISIT, $organization);
            if ($this->teacher instanceof Teacher && $this->teacher->getAcademicYear()->getOrganization() !== $organization) {
                throw $this->createAccessDeniedException();
            }
        }
        $this->denyAccessUnlessGranted(ContactVoter::ACCESS, $this->currentContact());
    }

    /**
     * Prepara los valores ANTES de que el formulario se envíe (el trait lo hace con prioridad 0):
     *  - «planes de formación» y «alumnado» empiezan siendo un <select> deshabilitado (valor texto) y pasan a ser
     *    casillas (lista) cuando hay opciones: se convierten en lista.
     *  - Al cambiar el centro de trabajo se vacían planes y alumnado; al cambiar la fecha o los planes, el alumnado
     *    (lo que se había elegido ya puede no corresponder). Se quita también su validación para que no aparezca
     *    «la opción seleccionada no es válida».
     */
    #[PreReRender(priority: 10)]
    public function prepareValues(): void
    {
        foreach (['trainingPrograms', 'studentEnrollments'] as $field) {
            $value = $this->formValues[$field] ?? [];
            if (!is_array($value)) {
                $this->formValues[$field] = $value === '' || $value === null ? [] : [$value];
            }
        }

        $workcenter = (string) ($this->formValues['workcenter'] ?? '');
        $dependencies = $this->dependencies();
        $clear = [];
        if ($workcenter !== $this->lastWorkcenter) {
            $clear = ['trainingPrograms', 'studentEnrollments'];
        } elseif ($dependencies !== $this->lastDependencies) {
            $clear = ['studentEnrollments'];
        }
        $this->lastWorkcenter = $workcenter;
        $this->lastDependencies = $dependencies;
        if ($clear === []) {
            return;
        }

        foreach ($clear as $field) {
            $this->formValues[$field] = [];
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
        $this->lastWorkcenter = (string) ($this->formValues['workcenter'] ?? '');
        $this->lastDependencies = $this->dependencies();
    }

    /** Fecha y planes de formación elegidos (de ellos depende el alumnado que se puede elegir). */
    private function dependencies(): string
    {
        $programs = (array) ($this->formValues['trainingPrograms'] ?? []);
        sort($programs);

        return json_encode([$this->formValues['dateTime']['date'] ?? '', $programs], JSON_THROW_ON_ERROR);
    }

    protected function instantiateForm(): FormInterface
    {
        $contact = $this->currentContact();
        $readOnly = !$this->isGranted(ContactVoter::MANAGE, $contact);

        return $this->createForm(ContactType::class, $contact, [
            'disabled' => $readOnly,
            'teachers' => $this->teacherProvider->forContact($contact, $readOnly),
        ]);
    }

    private ?Contact $newContact = null;

    private function currentContact(): Contact
    {
        if ($this->contactEntity !== null) {
            return $this->contactEntity;
        }

        $this->newContact ??= (new Contact())->setDateTime(new \DateTime());
        if ($this->teacher instanceof Teacher) {
            $this->newContact->setTeacher($this->teacher);
        }

        return $this->newContact;
    }
}
