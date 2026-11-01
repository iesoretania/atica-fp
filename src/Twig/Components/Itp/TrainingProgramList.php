<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\Person;
use App\Repository\Edu\GroupRepository;
use App\Repository\Edu\TeacherRepository;
use App\Repository\ItpModule\TrainingProgramRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Programas formativos de FFEOE de un curso académico, con filtros, orden y paginación dinámicos. */
#[AsLiveComponent('Itp:TrainingProgramList', template: 'components/Itp/TrainingProgramList.html.twig')]
final class TrainingProgramList extends AbstractListComponent
{
    private TrainingProgramRepository $trainingProgramRepository;
    private TeacherRepository $teacherRepository;
    private GroupRepository $groupRepository;

    #[Required]
    public function setRepositories(
        TrainingProgramRepository $trainingProgramRepository,
        TeacherRepository $teacherRepository,
        GroupRepository $groupRepository,
    ): void {
        $this->trainingProgramRepository = $trainingProgramRepository;
        $this->teacherRepository = $teacherRepository;
        $this->groupRepository = $groupRepository;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(ItpOrganizationVoter::ITP_MANAGER, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    public function filterDefinitions(): array
    {
        return [
            'modality' => [
                'type' => 'select',
                'label' => $this->translator->trans('header.modality', [], 'itp_training_program'),
                'choices' => [
                    '1' => $this->translator->trans('table.modality.1', [], 'itp_training_program'),
                    '2' => $this->translator->trans('table.modality.2', [], 'itp_training_program'),
                ],
            ],
            'locked' => [
                'type' => 'select',
                'label' => $this->translator->trans('header.locked', [], 'itp_training_program'),
                'choices' => ['1' => $this->translator->trans('filters.yes', [], 'general'), '0' => $this->translator->trans('filters.no', [], 'general')],
            ],
        ];
    }

    protected function sortableColumns(): array
    {
        return ['name' => 'tp.name', 'modality' => 'tp.modality', 'hours' => 'tp.targetHours'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $person = $this->security->getUser();
        if (!$person instanceof Person) {
            throw new AccessDeniedException();
        }
        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $this->organization());
        $teacher = $this->teacherRepository->findOneByAcademicYearAndPerson($this->academicYear, $person);

        // precarga grupos y niveles de los programas formativos del curso académico
        $this->groupRepository->findByAcademicYear($this->academicYear);

        $queryBuilder = $this->trainingProgramRepository->createProgramRepositoryQueryBuilder(
            $this->academicYear,
            $isManager,
            $person,
            $teacher,
            $this->q !== '' ? $this->q : null
        );

        if (($modality = $this->filter('modality')) !== '') {
            $queryBuilder->andWhere('tp.modality = :modality')->setParameter('modality', (int) $modality);
        }
        if (($locked = $this->filter('locked')) !== '') {
            $queryBuilder->andWhere('tp.locked = :locked')->setParameter('locked', $locked === '1');
        }

        return $queryBuilder;
    }
}
