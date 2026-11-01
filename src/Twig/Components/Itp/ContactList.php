<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\Person;
use App\Repository\Edu\ContactMethodRepository;
use App\Repository\ItpModule\ContactRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Contactos con empresas de FFEOE de un curso académico, con filtros (forma, fechas), orden y paginación dinámicos. */
#[AsLiveComponent('Itp:ContactList', template: 'components/Itp/ContactList.html.twig')]
final class ContactList extends AbstractListComponent
{
    private ContactRepository $contactRepository;
    private ContactMethodRepository $contactMethodRepository;

    #[Required]
    public function setRepositories(ContactRepository $contactRepository, ContactMethodRepository $contactMethodRepository): void
    {
        $this->contactRepository = $contactRepository;
        $this->contactMethodRepository = $contactMethodRepository;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(ItpOrganizationVoter::ITP_ACCESS_VISIT, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    public function allowNew(): bool
    {
        return $this->security->isGranted(ItpOrganizationVoter::ITP_CREATE_VISIT, $this->organization());
    }

    protected function fetchJoinCollection(): bool
    {
        return true;
    }

    public function filterDefinitions(): array
    {
        $methods = ['0' => $this->translator->trans('form.on-site', [], 'itp_contact')];
        foreach ($this->contactMethodRepository->findEnabledByAcademicYear($this->academicYear) as $method) {
            $methods[(string) $method->getId()] = (string) $method->getDescription();
        }

        return [
            'method' => ['type' => 'select', 'label' => $this->translator->trans('header.method', [], 'itp_contact'), 'choices' => $methods],
            'from' => ['type' => 'date', 'label' => $this->translator->trans('filters.date_from', [], 'general')],
            'to' => ['type' => 'date', 'label' => $this->translator->trans('filters.date_to', [], 'general')],
        ];
    }

    protected function sortableColumns(): array
    {
        return ['datetime' => 'c.dateTime', 'teacher' => ['p.lastName', 'p.firstName'], 'method' => 'm.description'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $person = $this->security->getUser();
        if (!$person instanceof Person) {
            throw new AccessDeniedException();
        }
        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $this->organization());

        $queryBuilder = $this->contactRepository->createContactQueryBuilder(
            $this->academicYear,
            $person,
            $isManager,
            [],
            $this->q !== '' ? $this->q : null
        );

        $method = $this->filter('method');
        if ($method === '0') {
            $queryBuilder->andWhere('c.method IS NULL');
        } elseif ($method !== '') {
            $queryBuilder->andWhere('c.method = :filter_method')->setParameter('filter_method', (int) $method);
        }
        if (($from = $this->filter('from')) !== '') {
            $queryBuilder->andWhere('c.dateTime >= :filter_from')->setParameter('filter_from', new \DateTime($from . ' 00:00:00'));
        }
        if (($to = $this->filter('to')) !== '') {
            $queryBuilder->andWhere('c.dateTime < :filter_to')->setParameter('filter_to', (new \DateTime($to . ' 00:00:00'))->modify('+1 day'));
        }

        return $queryBuilder;
    }
}
