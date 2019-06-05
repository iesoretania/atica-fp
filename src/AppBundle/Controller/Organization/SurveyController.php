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

namespace AppBundle\Controller\Organization;

use AppBundle\Entity\Organization;
use AppBundle\Entity\Survey;
use AppBundle\Form\Type\SurveyType;
use AppBundle\Repository\SurveyRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Security\SurveyVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/centro/encuesta")
 */
class SurveyController extends Controller
{
    /**
     * @Route("/nueva", name="organization_survey_new", methods={"GET", "POST"})
     * @Route("/{id}", name="organization_survey_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        Survey $survey = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);
        if ($survey) {
            $this->denyAccessUnlessGranted(SurveyVoter::MANAGE, $survey);
        }

        $em = $this->getDoctrine()->getManager();

        if (null === $survey) {
            $survey = new Survey();
            $survey
                ->setOrganization($organization);
            $em->persist($survey);
        }

        $form = $this->createForm(SurveyType::class, $survey);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'survey'));
                return $this->redirectToRoute('organization_survey_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.save_error', [], 'survey'));
            }
        }

        $title = $translator->trans(
            $survey->getId() ? 'title.edit' : 'title.new',
            [],
            'survey'
        );

        $breadcrumb = [
            $survey->getId() ?
                ['fixed' => $survey->getTitle()] :
                ['fixed' => $translator->trans('title.new', [], 'survey')]
        ];

        return $this->render('organization/survey/form.html.twig', [
            'menu_path' => 'organization_survey_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{page}", name="organization_survey_list", requirements={"page" = "\d+"},
     *     defaults={"page" = 1},  methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('s')
            ->from(Survey::class, 's')
            ->orderBy('s.title');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('s.title LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('s.organization = :organization')
            ->setParameter('organization', $organization);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $this->get('translator')->trans('title.list', [], 'survey');

        return $this->render('organization/survey/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'survey'
        ]);
    }

    /**
     * @Route("/operacion", name="organization_survey_operation", methods={"POST"})
     */
    public function operationAction(
        Request $request,
        SurveyRepository $surveyRepository,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('organization_survey_list');
        }

        if ($request->request->has('purge')) {
            return $this->processPurge($request, $surveyRepository, $translator, $items, $organization);
        }

        // borrar encuestas
        return $this->processDelete($request, $surveyRepository, $translator, $items, $organization);
    }

    /**
     * @param Request $request
     * @param SurveyRepository $surveyRepository
     * @param TranslatorInterface $translator
     * @param $items
     * @param Organization $organization
     * @return Response
     */
    private function processPurge(
        Request $request,
        SurveyRepository $surveyRepository,
        TranslatorInterface $translator,
        $items,
        Organization $organization
    ) {
        $em = $this->getDoctrine()->getManager();

        $surveys = $surveyRepository->findAllInListByIdAndOrganization($items, $organization);
        if (count($surveys) === 0) {
            return $this->redirectToRoute('organization_survey_list');
        }
        if ($request->get('confirm', '') === 'ok') {
            try {
                $surveyRepository->purgeAnswersFromList($surveys);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.purged', [], 'survey'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.purge_error', [], 'survey'));
            }
            return $this->redirectToRoute('organization_survey_list');
        }

        return $this->render('organization/survey/purge.html.twig', [
            'menu_path' => 'organization_survey_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.purge', [], 'survey')]],
            'title' => $translator->trans('title.purge', [], 'survey'),
            'items' => $surveys
        ]);
    }

    /**
     * @param Request $request
     * @param SurveyRepository $surveyRepository
     * @param TranslatorInterface $translator
     * @param $items
     * @param Organization $organization
     * @return Response
     */
    private function processDelete(
        Request $request,
        SurveyRepository $surveyRepository,
        TranslatorInterface $translator,
        $items,
        Organization $organization
    ) {
        $em = $this->getDoctrine()->getManager();

        $surveys = $surveyRepository->findAllInListByIdAndOrganizationAndNoAnswers($items, $organization);
        if (count($surveys) === 0) {
            return $this->redirectToRoute('organization_survey_list');
        }

        if ($request->get('confirm', '') === 'ok') {
            try {
                $surveyRepository->deleteFromList($surveys);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'survey'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'survey'));
            }
            return $this->redirectToRoute('organization_survey_list');
        }

        return $this->render('organization/survey/delete.html.twig', [
            'menu_path' => 'organization_survey_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'survey')]],
            'title' => $translator->trans('title.delete', [], 'survey'),
            'items' => $surveys
        ]);
    }
}
