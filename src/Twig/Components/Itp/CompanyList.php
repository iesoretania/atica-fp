<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\ItpModule\ProgramGrade;
use App\Repository\ItpModule\CompanyProgramRepository;
use App\Security\ItpModule\TrainingProgramVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Empresas colaboradoras de un programa formativo de FFEOE (por ciclo), con filtro, orden y paginación dinámicos. */
#[AsLiveComponent('Itp:CompanyList', template: 'components/Itp/CompanyList.html.twig')]
final class CompanyList extends AbstractListComponent
{
    #[LiveProp]
    public ProgramGrade $programGrade;

    private CompanyProgramRepository $companyProgramRepository;

    #[Required]
    public function setCompanyProgramRepository(CompanyProgramRepository $companyProgramRepository): void
    {
        $this->companyProgramRepository = $companyProgramRepository;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(TrainingProgramVoter::MANAGE, $this->programGrade->getTrainingProgram());
        if ($this->programGrade->getGrade()->getTraining()->getAcademicYear()->getOrganization() !== $this->organization()) {
            throw new AccessDeniedException();
        }
    }

    protected function fetchJoinCollection(): bool
    {
        return true;
    }

    protected function sortableColumns(): array
    {
        return ['code' => 'c.code', 'name' => 'c.name', 'agreement' => 'cp.agreementNumber'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        return $this->companyProgramRepository->createByProgramGradeQueryBuilder($this->programGrade, $this->q !== '' ? $this->q : null);
    }

    /** Número de actividades, resultados de aprendizaje y criterios de cada empresa (por id de la relación empresa-programa). */
    public function getStats(): array
    {
        return $this->companyProgramRepository->getCompanyProgramStats($this->programGrade);
    }
}
