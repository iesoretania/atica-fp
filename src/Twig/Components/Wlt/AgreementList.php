<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\Person;
use App\Entity\WltModule\Agreement;
use App\Entity\WltModule\Project;
use App\Repository\WltModule\AgreementRepository;
use App\Security\WltModule\ProjectVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Convenios de un proyecto de FP dual, con filtro por grupo, orden y paginación dinámicos. */
#[AsLiveComponent('Wlt:AgreementList', template: 'components/Wlt/AgreementList.html.twig')]
final class AgreementList extends AbstractListComponent
{
    #[LiveProp]
    public Project $project;

    private AgreementRepository $agreementRepository;

    #[Required]
    public function setAgreementRepository(AgreementRepository $agreementRepository): void
    {
        $this->agreementRepository = $agreementRepository;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(ProjectVoter::MANAGE, $this->project);
        if ($this->project->getOrganization() !== $this->organization()) {
            throw new AccessDeniedException();
        }
    }

    public function filterDefinitions(): array
    {
        $groups = [];
        foreach ($this->project->getGroups() as $group) {
            $groups[(string) $group->getId()] = (string) $group->getName();
        }
        asort($groups);

        return [
            'group' => [
                'type' => 'select',
                'label' => $this->translator->trans('header.group', [], 'wlt_agreement'),
                'choices' => $groups,
            ],
        ];
    }

    protected function sortableColumns(): array
    {
        return [
            'student' => ['g.name', 'p.lastName', 'p.firstName'],
            'workcenter' => ['c.name', 'w.name'],
            'start' => 'a.startDate',
        ];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $person = $this->security->getUser();
        if (!$person instanceof Person) {
            throw new AccessDeniedException();
        }

        $queryBuilder = $this->newQueryBuilder()
            ->select('a', 'w', 'c', 'se', 'p', 'g', 'gr', 't', 'wt', 'et', 'etp')
            ->from(Agreement::class, 'a')
            ->join('a.workcenter', 'w')
            ->join('a.educationalTutor', 'et')
            ->join('et.person', 'etp')
            ->leftJoin('a.additionalEducationalTutor', 'aet')
            ->leftJoin('aet.person', 'aetp')
            ->join('w.company', 'c')
            ->join('a.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('a.workTutor', 'wt')
            ->leftJoin('a.additionalWorkTutor', 'awt')
            ->join('a.project', 'pro')
            ->orderBy('g.name')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('a.startDate')
            ->addOrderBy('c.name');

        if ($this->q !== '') {
            $queryBuilder
                ->where('g.name LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('etp.lastName LIKE :tq')
                ->orWhere('etp.firstName LIKE :tq')
                ->orWhere('aetp.lastName LIKE :tq')
                ->orWhere('aetp.firstName LIKE :tq')
                ->orWhere('w.name LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('wt.firstName LIKE :tq')
                ->orWhere('wt.lastName LIKE :tq')
                ->orWhere('wt.uniqueIdentifier LIKE :tq')
                ->orWhere('awt.firstName LIKE :tq')
                ->orWhere('awt.lastName LIKE :tq')
                ->orWhere('awt.uniqueIdentifier LIKE :tq')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        $this->agreementRepository->setQueryBuilderFilterByOrganizationManagerPersonProjectAndReturnProjects(
            $queryBuilder,
            $this->organization(),
            $person,
            $this->project
        );

        if (($group = $this->filter('group')) !== '') {
            $queryBuilder->andWhere('g.id = :filter_group')->setParameter('filter_group', (int) $group);
        }

        return $queryBuilder;
    }
}
