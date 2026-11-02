<?php

declare(strict_types=1);

namespace App\Twig\Components\Admin;

use App\Entity\Person;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Usuarios de la aplicación (solo administración global). */
#[AsLiveComponent('Admin:UserList', template: 'components/Admin/UserList.html.twig')]
final class UserList extends AbstractListComponent
{
    protected function authorize(): void
    {
        $this->denyUnlessGranted('ROLE_ADMIN');
    }

    public function currentUserId(): ?int
    {
        $user = $this->security->getUser();

        return $user instanceof Person ? $user->getId() : null;
    }

    protected function sortableColumns(): array
    {
        return [
            'name' => ['p.lastName', 'p.firstName'],
            'username' => 'p.loginUsername',
            'last_login' => 'p.lastAccess',
        ];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('p')
            ->from(Person::class, 'p')
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName');

        if ($this->q !== '') {
            $search = $queryBuilder->expr()->orX(
                'p.loginUsername LIKE :tq',
                'p.firstName LIKE :tq',
                'p.lastName LIKE :tq',
                'p.emailAddress LIKE :tq',
                'p.uniqueIdentifier LIKE :tq',
            );
            if (ctype_digit($this->q)) {
                $search->add('p.id = :q');
                $queryBuilder->setParameter('q', (int) $this->q);
            }
            $queryBuilder->andWhere($search)->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder;
    }
}
