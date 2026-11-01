<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\Person;
use App\Repository\ItpModule\TrainingProgramRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Programas formativos de FFEOE con enlace a su informe de encuestas (alumnado, empresas o centro), por curso académico. */
#[AsLiveComponent('Itp:ReportList', template: 'components/Itp/ReportList.html.twig')]
final class ReportList extends AbstractListComponent
{
    /** Ruta del informe al que enlaza cada programa (se firma con el resto de propiedades). */
    #[LiveProp]
    public string $routeName = '';

    private TrainingProgramRepository $trainingProgramRepository;

    #[Required]
    public function setTrainingProgramRepository(TrainingProgramRepository $trainingProgramRepository): void
    {
        $this->trainingProgramRepository = $trainingProgramRepository;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(ItpOrganizationVoter::ITP_MANAGER, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function sortableColumns(): array
    {
        return ['name' => 'tp.name', 'modality' => 'tp.modality'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $person = $this->security->getUser();
        if (!$person instanceof Person) {
            throw new AccessDeniedException();
        }

        return $this->trainingProgramRepository->createByManagerQueryBuilder(
            $this->academicYear,
            $this->security->isGranted(OrganizationVoter::MANAGE, $this->organization()),
            $person,
            $this->q !== '' ? $this->q : null
        );
    }
}
