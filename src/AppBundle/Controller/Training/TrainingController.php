<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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

namespace AppBundle\Controller\Training;

use AppBundle\Entity\Edu\Training;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/ensenanza")
 */
class TrainingController extends Controller
{
    /**
     * @Route("/listar/{page}", name="training", requirements={"page" = "\d+"},
     *     defaults={"page" = 1},   methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
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
        $f = $request->get('f', 0);
        if ($q) {
            $queryBuilder
                ->where('t.name LIKE :tq')
                ->orWhere('t.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        if (false === $this->isGranted(OrganizationVoter::MANAGE, $organization)) {
            $f = 1;
        }

        switch ($f) {
            case 1:
                $queryBuilder
                    ->andWhere('t.workLinked = :on')
                    ->setParameter('on', true);
                break;
        }

        $queryBuilder
            ->andWhere('t.department IS NOT NULL')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        if (false === $this->isGranted(OrganizationVoter::MANAGE_WORKLINKED_TRAINING, $organization)) {
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

        return $this->render('training/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'f' => $f,
            'domain' => 'edu_training'
        ]);
    }
}
