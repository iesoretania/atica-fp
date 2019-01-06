<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see [http://www.gnu.org/licenses/].
*/

namespace AppBundle\Controller\WLT;

use AppBundle\Entity\Edu\Training;
use AppBundle\Entity\Role;
use AppBundle\Repository\RoleRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/dual/ensenanza")
 */
class TrainingController extends Controller
{
    /**
     * @Route("/listar/{page}", name="work_linked_training_training", requirements={"page" = "\d+"},
     *     defaults={"page" = 1}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        Security $security,
        RoleRepository $roleRepository,
        $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $academicYear = $organization->getCurrentAcademicYear();

        $this->denyAccessUnlessGranted(OrganizationVoter::ACCESS_TRAININGS, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('t')
            ->from(Training::class, 't')
            ->orderBy('t.name');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('t.name LIKE :tq')
                ->orWhere('t.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('t.workLinked = :on')
            ->setParameter('on', true)
            ->andWhere('t.department IS NOT NULL')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        if (false === $security->isGranted(OrganizationVoter::MANAGE, $organization) &&
            false === $roleRepository->personHasRole(
                $organization,
                $this->getUser()->getPerson(),
                Role::ROLE_WLT_MANAGER
            )
        ) {
            $queryBuilder
                ->join('t.department', 'd')
                ->join('d.head', 'te')
                ->andWhere('te.person = :person')
                ->setParameter('person', $this->getUser()->getPerson());
        }

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $this->get('translator')->trans('title.list', [], 'edu_training');

        return $this->render('wlt/training/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_training'
        ]);
    }
}
