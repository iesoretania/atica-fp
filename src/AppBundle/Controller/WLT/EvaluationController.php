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
use AppBundle\Entity\WLT\Agreement;
use AppBundle\Form\Type\WLT\AgreementEvaluationType;
use AppBundle\Repository\Edu\GroupRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\RoleRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Security\WLT\AgreementVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/dual/convenio/evaluar")
 */
class EvaluationController extends Controller
{
    /**
     * @Route("/{id}", name="work_linked_training_evaluation_form",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        TranslatorInterface $translator,
        Agreement $agreement
    ) {
        $this->denyAccessUnlessGranted(AgreementVoter::VIEW_GRADE, $agreement);

        $academicYear = $agreement->
            getStudentEnrollment()->getGroup()->getGrade()->getTraining()->getAcademicYear();

        $em = $this->getDoctrine()->getManager();

        $readOnly = false === $this->isGranted(AgreementVoter::GRADE, $agreement);

        $form = $this->createForm(AgreementEvaluationType::class, $agreement, [
            'disabled' => $readOnly
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_agreement'));
                return $this->redirectToRoute('work_linked_training_evaluation_list', [
                    'academicYear' => $academicYear
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_agreement'));
            }
        }

        $title = $translator->trans('title.grade', [], 'wlt_agreement_activity_realization');

        $breadcrumb = [
                ['fixed' => (string) $agreement],
                ['fixed' => $title]
        ];

        return $this->render('wlt/evaluation/form.html.twig', [
            'menu_path' => 'work_linked_training_evaluation_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'agreement' => $agreement,
            'read_only' => $readOnly,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{academicYear}/{page}", name="work_linked_training_evaluation_list",
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
            ->select('a')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('se')
            ->addSelect('p')
            ->addSelect('g')
            ->from(Agreement::class, 'a')
            ->join('a.workcenter', 'w')
            ->join('w.company', 'c')
            ->join('a.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('a.workTutor', 'wt')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('c.name');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('g.name LIKE :tq')
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

        $person = $this->getUser()->getPerson();
        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $security->isGranted(OrganizationVoter::WLT_MANAGER, $organization);
        $isDepartmentHead = $security->isGranted(OrganizationVoter::DEPARTMENT_HEAD, $organization);
        $isWorkTutor = $security->isGranted(OrganizationVoter::WLT_WORK_TUTOR, $organization);
        $isGroupTutor = $security->isGranted(OrganizationVoter::WLT_GROUP_TUTOR, $organization);
        $isTeacher = $security->isGranted(OrganizationVoter::WLT_TEACHER, $organization);

        if (false === $isManager && false === $isWltManager) {
            // si es tutor laboral, mostrar siempre
            if ($isWorkTutor) {
                $queryBuilder
                    ->orWhere('a.workTutor = :person')
                    ->setParameter('person', $person);
            }

            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento, tutor de grupo o profesor

            // vamos a buscar los grupos a los que tienen acceso
            $groups = new ArrayCollection();

            $teacher =
                $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

            if ($isDepartmentHead) {
                $newGroups = $groupRepository->findByAcademicYearAndWltHead($academicYear, $teacher);
                $this->appendGroups($groups, $newGroups);
            }
            if ($isGroupTutor) {
                $newGroups = $groupRepository->findByAcademicYearAndWltTutor($academicYear, $teacher);
                $this->appendGroups($groups, $newGroups);
            }
            if ($isTeacher) {
                $newGroups = $groupRepository->findByAcademicYearAndWltTeacher($academicYear, $teacher);
                $this->appendGroups($groups, $newGroups);
            }
            if ($groups->count() > 0) {
                $queryBuilder
                    ->orWhere('g IN (:groups)')
                    ->setParameter('groups', $groups);
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

        return $this->render('wlt/evaluation/list.html.twig', [
            'title' => $title . ' - ' . $academicYear,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_agreement_activity_realization',
            'academic_year' => $academicYear
        ]);
    }

    private function appendGroups(ArrayCollection $groups, $newGroups)
    {
        foreach ($newGroups as $group) {
            if (false === $groups->contains($group)) {
                $groups->add($group);
            }
        }
    }
}
