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

use AppBundle\Entity\WLT\Agreement;
use AppBundle\Entity\WLT\Project;
use AppBundle\Repository\WLT\ProjectRepository;
use AppBundle\Repository\WLT\WLTGroupRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Security\WLT\WLTOrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/dual/seguimiento")
 */
class TrackingController extends Controller
{
    /**
     * @Route("/estudiante/listar/{project}/{page}", name="work_linked_training_tracking_list",
     *     requirements={"page" = "\d+"}, defaults={"project" = null, "page" = 1}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        WLTGroupRepository $WLTGroupRepository,
        ProjectRepository $projectRepository,
        Security $security,
        $page = 1,
        Project $project = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_ACCESS, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('a')
            ->addSelect('pro')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('se')
            ->addSelect('p')
            ->addSelect('g')
            ->addSelect('SUM(wd.hours)')
            ->addSelect('SUM(CASE WHEN wd.absence = 0 THEN wd.locked * wd.hours ELSE 0 END)')
            ->addSelect('SUM(CASE WHEN wd.absence != 0 THEN 1 ELSE 0 END)')
            ->addSelect('SUM(CASE WHEN wd.absence = 2 THEN 1 ELSE 0 END)')
            ->from(Agreement::class, 'a')
            ->leftJoin('a.workDays', 'wd')
            ->join('a.project', 'pro')
            ->join('a.workcenter', 'w')
            ->join('w.company', 'c')
            ->join('a.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('a.workTutor', 'wt')
            ->groupBy('a')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('a.startDate')
            ->addOrderBy('c.name');

        $q = $request->get('q');

        if ($q) {
            $queryBuilder
                ->orWhere('g.name LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('w.name LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('g.name LIKE :tq')
                ->orWhere('wt.firstName LIKE :tq')
                ->orWhere('wt.lastName LIKE :tq')
                ->orWhere('wt.uniqueIdentifier LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);
        $isWorkTutor = $security->isGranted(WLTOrganizationVoter::WLT_WORK_TUTOR, $organization);

        $person = $this->getUser()->getPerson();

        $projects= [];
        if ($isWltManager) {
            if (!$isManager) {
                $projects = $projectRepository->findByOrganizationAndManagerPerson($organization, $person);
            } else {
                $projects = $projectRepository->findByOrganization($organization);
            }
        }

        if (false === $isManager && false === $isWltManager) {
            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento, tutor de grupo o profesor
            $groups =
                $WLTGroupRepository->findByOrganizationAndPerson($organization, $person);

            if (!$groups->isEmpty()) {
                $queryBuilder
                    ->andWhere('g IN (:groups)')
                    ->setParameter('groups', $groups);
            }

            // si solo es tutor laboral, necesita ser el tutor para verlo
            if ($isWorkTutor) {
                $queryBuilder
                    ->andWhere('a.workTutor = :person')
                    ->setParameter('person', $person);
            }

            $queryBuilder
                ->orWhere('p = :person')
                ->setParameter('person', $person);
        }

        if ($project) {
            $queryBuilder
                ->andWhere('a.project = :project')
                ->setParameter('project', $project);
        } elseif ($projects && !$isManager) {
            $queryBuilder
                ->andWhere('a.project IN (:projects)')
                ->setParameter('projects', $projects);
        }

        $queryBuilder
            ->andWhere('pro.organization = :organization')
            ->setParameter('organization', $organization);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $translator->trans('title.agreement.list', [], 'wlt_tracking');

        return $this->render('wlt/tracking/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_tracking',
            'project' => $project,
            'projects' => $projects
        ]);
    }
}
