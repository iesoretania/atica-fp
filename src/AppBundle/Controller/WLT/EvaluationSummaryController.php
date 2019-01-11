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

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\WLT\Agreement;
use AppBundle\Repository\Edu\GroupRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Security\OrganizationVoter;
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
 * @Route("/dual/resultado/estudiante")
 */
class EvaluationSummaryController extends Controller
{
    /**
     * @Route("/listar/{academicYear}/{page}", name="work_linked_training_evaluation_summary_list",
     *     requirements={"page" = "\d+"}, defaults={"academicYear" = null, "page" = 1}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        TeacherRepository $teacherRepository,
        GroupRepository $groupRepository,
        Security $security,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if (null === $academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW_GRADE_WORK_LINKED_TRAINING, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->addSelect('se')
            ->addSelect('p')
            ->addSelect('g')
            ->addSelect('COUNT(DISTINCT a)')
            ->addSelect('COUNT(ar)')
            ->addSelect('COUNT(ar.grade)')
            ->from(StudentEnrollment::class, 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join(Agreement::class, 'a', 'WITH', 'a.studentEnrollment = se')
            ->join('a.evaluatedActivityRealizations', 'ar')
            ->groupBy('se')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('g.name');

        $q = $request->get('q');

        if ($q) {
            $queryBuilder
                ->orWhere('g.name LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $security->isGranted(OrganizationVoter::WLT_MANAGER, $organization);
        $isWorkTutor = $security->isGranted(OrganizationVoter::WLT_WORK_TUTOR, $organization);

        if (false === $isManager && false === $isWltManager) {
            $person = $this->getUser()->getPerson();

            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento, tutor de grupo o profesor
            $teacher =
                $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

            if ($teacher) {
                $groups = $groupRepository->findByAcademicYearAndTeacher($academicYear, $teacher);

                if ($groups->count() > 0) {
                    $queryBuilder
                        ->andWhere('g IN (:groups)')
                        ->setParameter('groups', $groups);
                }
                // si también es tutor laboral, mostrar los suyos aunque sean de otros grupos
                if ($isWorkTutor) {
                    $queryBuilder
                        ->orWhere('a.workTutor = :person')
                        ->setParameter('person', $person);
                }
            } else {
                // si solo es tutor laboral, necesita ser el tutor para verlo
                if ($isWorkTutor) {
                    $queryBuilder
                        ->andWhere('a.workTutor = :person')
                        ->setParameter('person', $person);
                }
            }
        }

        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $translator->trans('title.list', [], 'wlt_agreement_activity_realization');

        return $this->render('wlt/evaluation/summary.html.twig', [
            'title' => $title . ' - ' . $academicYear,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_agreement_activity_realization',
            'academic_year' => $academicYear
        ]);
    }
}
