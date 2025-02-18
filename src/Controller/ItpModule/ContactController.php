<?php
/*
  Copyright (C) 2018-2025: Luis Ramón López López

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

namespace App\Controller\ItpModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\Contact;
use App\Entity\Person;
use App\Form\Type\ItpModule\ContactType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\ContactMethodRepository;
use App\Repository\ItpModule\ContactRepository;
use App\Repository\ItpModule\TeacherRepository as ItpTeacherRepository;
use App\Security\ItpModule\ContactVoter;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/formacion/contacto')]
class ContactController extends AbstractController
{
    #[Route(path: '/nuevo', name: 'in_company_training_phase_contact_new', methods: ['GET', 'POST'])]
    public function new(
        Request              $request,
        TranslatorInterface  $translator,
        UserExtensionService $userExtensionService,
        Security             $security,
        ItpTeacherRepository $itpTeacherRepository,
        ContactRepository    $contactRepository
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_CREATE_VISIT, $organization);

        $academicYear = $organization->getCurrentAcademicYear();
        assert($academicYear instanceof AcademicYear);

        $person = $this->getUser();
        assert($person instanceof Person);

        $teacher = $itpTeacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

        $contact = new Contact();
        $contact
            ->setDateTime(new \DateTime());

        if ($teacher) {
            $contact->setTeacher($teacher);
        }

        $contactRepository->persist($contact);

        return $this->index(
            $request,
            $translator,
            $userExtensionService,
            $security,
            $itpTeacherRepository,
            $contactRepository,
            $contact
        );
    }

    #[Route(path: '/{id}', name: 'in_company_training_phase_contact_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request              $request,
        TranslatorInterface  $translator,
        UserExtensionService $userExtensionService,
        Security             $security,
        ItpTeacherRepository $itpTeacherRepository,
        ContactRepository    $contactRepository,
        Contact              $contact
    ): Response {
        $this->denyAccessUnlessGranted(ContactVoter::ACCESS, $contact);

        $organization = $userExtensionService->getCurrentOrganization();
        $academicYear = $contact->getTeacher() instanceof Teacher
            ? $contact->getTeacher()->getAcademicYear()
            : $organization->getCurrentAcademicYear();

        assert($academicYear instanceof AcademicYear);

        $readOnly = !$this->isGranted(ContactVoter::MANAGE, $contact);
        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);

        $person = $this->getUser();
        assert($person instanceof Person);

        $teacher = $itpTeacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

        if (!$isManager) {
            if ($teacher instanceof Teacher) {
                $teachers = [$teacher];
            } else {
                $teachers = [];
            }
        } else {
            $teachers = $itpTeacherRepository->findByAcademicYear($academicYear);
        }

        if ($readOnly && $contact->getTeacher() instanceof Teacher) {
            $teachers = [$contact->getTeacher()];
        }

        $form = $this->createForm(ContactType::class, $contact, [
            'disabled' => $readOnly,
            'teachers' => $teachers
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $contactRepository->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'itp_contact'));
                return $this->redirectToRoute('in_company_training_phase_contact_list');
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'itp_contact'));
            }
        }

        $title = $translator->trans(
            $contact->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'itp_contact'
        );

        $breadcrumb = [
                ['fixed' => $title]
        ];

        return $this->render('itp/contact/form.html.twig', [
            'menu_path' => 'in_company_training_phase_contact_list',
            'academic_year' => $academicYear,
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'read_only' => $readOnly,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/listar/{academicYear}/{page}', name: 'in_company_training_phase_contact_list', requirements: ['page' => '\d+'], methods: ['GET'])]
    public function list(
        Request                 $request,
        UserExtensionService    $userExtensionService,
        Security                $security,
        TranslatorInterface     $translator,
        AcademicYearRepository  $academicYearRepository,
        ContactMethodRepository $contactMethodRepository,
        ContactRepository       $contactRepository,
        AcademicYear            $academicYear = null,
        int                     $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_ACCESS_VISIT, $organization);
        $allowNew = $this->isGranted(ItpOrganizationVoter::ITP_CREATE_VISIT, $organization);

        $mf = $request->get('mf');

        $methodCollection = [];
        if (null !== $mf) {
            $methodIdsCollection = explode(',', (string) $mf);
            $methodCollection = $contactMethodRepository
                ->findAllInListByIdAndAcademicYear($methodIdsCollection, $academicYear);
            if (in_array('0', $methodIdsCollection, true)) {
                $methodCollection[] = null;
            }
        }

        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $person = $this->getUser();
        assert($person instanceof Person);

        $q = $request->get('q');

        $queryBuilder = $contactRepository->createContactQueryBuilder($academicYear, $person, $isManager, $methodCollection, $q);

        $adapter = new QueryAdapter($queryBuilder, true);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'itp_contact');

        $methods = $contactMethodRepository->findEnabledByAcademicYear($academicYear);

        $activeMethods = [];
        $activeMethods = array_merge($activeMethods, $methodCollection);

        return $this->render('itp/contact/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_contact',
            'allow_new' => $allowNew,
            'methods' => $methods,
            'active_methods' => $activeMethods,
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/eliminar', name: 'in_company_training_phase_contact_operation', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function operation(
        Request              $request,
        ContactRepository    $contactRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface  $translator
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_ACCESS_VISIT, $organization);

        $items = $request->request->all('items');
        if (count($items) === 0) {
            return $this->redirectToRoute('in_company_training_phase_contact_list');
        }

        $visits = $contactRepository->findAllInListById($items);
        foreach ($visits as $visit) {
            $this->denyAccessUnlessGranted(ContactVoter::MANAGE, $visit);
        }

        if ($request->get('confirm', '') === 'ok') {
            try {
                foreach ($visits as $visit) {
                    $contactRepository->remove($visit);
                }
                $contactRepository->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'itp_contact'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'itp_contact'));
            }
            return $this->redirectToRoute('in_company_training_phase_contact_list');
        }

        $title = $translator->trans('title.delete', [], 'itp_contact');
        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('itp/contact/delete.html.twig', [
            'menu_path' => 'in_company_training_phase_contact_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $visits
        ]);
    }
}
