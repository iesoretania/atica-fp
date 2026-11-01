<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\Edu\Teacher;
use App\Form\Model\WltModule\ContactEducationalTutorReport;
use App\Form\Type\WltModule\ContactEducationalTutorReportType;
use App\Repository\Edu\ContactMethodRepository;
use App\Repository\WltModule\ProjectRepository;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Service\UserExtensionService;
use App\Service\WltModule\ContactReportTeacherProvider;
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
 * Formulario del informe de contactos de un docente de seguimiento: al cambiar los proyectos a incluir se recalculan
 * los centros de trabajo que se pueden elegir (sustituye a assets/js/wlt/contact_educational_tutor_report.js).
 *
 * El informe NO se genera aquí: el <form> se envía normalmente a ContactController, que devuelve el PDF. El componente solo
 * repinta el formulario mientras se edita.
 */
#[AsLiveComponent('Wlt:ContactEducationalTutorReportForm', template: 'components/Wlt/ContactEducationalTutorReportForm.html.twig')]
final class ContactEducationalTutorReportForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public Teacher $teacher;

    /** Enlace del botón «Volver» (se firma junto al resto de propiedades). */
    #[LiveProp]
    public string $backUrl = '';

    /** Últimos proyectos elegidos: al cambiarlos se vacía el centro de trabajo. */
    #[LiveProp]
    public string $lastProjects = '';

    public function __construct(
        private readonly UserExtensionService $userExtensionService,
        private readonly ContactReportTeacherProvider $reportTeachers,
        private readonly ContactMethodRepository $contactMethodRepository,
        private readonly ProjectRepository $projectRepository,
    ) {
    }

    public function getSubmitUrl(): string
    {
        return $this->generateUrl('work_linked_training_contact_educational_tutor_report_form', ['teacher' => $this->teacher->getId()]);
    }

    /** Se comprueba en cada petición al componente, igual que hace el controlador de la página en el primer pintado. */
    #[PostHydrate]
    public function authorize(): void
    {
        $this->denyAccessUnlessGranted(WltOrganizationVoter::WLT_ACCESS_VISIT, $this->userExtensionService->getCurrentOrganization());
        if (!in_array($this->teacher, $this->reportTeachers->forAcademicYear($this->teacher->getAcademicYear()), true)) {
            throw $this->createAccessDeniedException();
        }
    }

    #[PreReRender(priority: 10)]
    public function prepareValues(): void
    {
        foreach (['projects', 'contactMethods'] as $field) {
            $value = $this->formValues[$field] ?? [];
            if (!is_array($value)) {
                $this->formValues[$field] = $value === '' || $value === null ? [] : [$value];
            }
        }

        $projects = $this->projectsKey();
        if ($projects === $this->lastProjects) {
            return;
        }
        $this->lastProjects = $projects;
        $this->formValues['workcenter'] = '';
        $path = $this->getFormName() . '.workcenter';
        $this->validatedFields = array_values(array_filter($this->validatedFields, static fn (string $p): bool => $p !== $path));
    }

    #[PostMount(priority: -1)]
    public function rememberValues(): void
    {
        $this->lastProjects = $this->projectsKey();
    }

    private function projectsKey(): string
    {
        $projects = (array) ($this->formValues['projects'] ?? []);
        sort($projects);

        return json_encode($projects, JSON_THROW_ON_ERROR);
    }

    protected function instantiateForm(): FormInterface
    {
        $report = new ContactEducationalTutorReport();
        $report->setTeacher($this->teacher);
        $methods = $this->contactMethodRepository->findEnabledByAcademicYear($this->teacher->getAcademicYear());
        if (is_array($methods)) {
            $methods[] = null;
        }
        $report->setContactMethods($methods);
        $projects = $this->projectRepository->findByEducationalTutor($this->teacher);
        array_unshift($projects, null);
        $report->setProjects($projects);

        return $this->createForm(ContactEducationalTutorReportType::class, $report, ['teacher' => $this->teacher]);
    }
}
