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
use AppBundle\Entity\SurveyQuestion;
use AppBundle\Form\Type\SurveyQuestionType;
use AppBundle\Repository\SurveyQuestionRepository;
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
 * @Route("/centro/encuesta/pregunta")
 */
class SurveyQuestionController extends Controller
{
    /**
     * @Route("/nueva/{id}", name="organization_survey_question_new",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        TranslatorInterface $translator,
        SurveyQuestionRepository $surveyQuestionRepository,
        Survey $survey
    ) {
        $this->denyAccessUnlessGranted(SurveyVoter::MANAGE, $survey);

        if ($survey->getAnswers()->count() > 0) {
            return $this->redirectToRoute('organization_survey_question_list', ['id' => $survey->getId()]);
        }

        $surveyQuestion = new SurveyQuestion();
        $lastOrderNr = $surveyQuestionRepository->getLastOrderNr($survey);
        $surveyQuestion
            ->setSurvey($survey)
            ->setOrderNr($lastOrderNr + 1);

        $this->getDoctrine()->getManager()->persist($surveyQuestion);

        return $this->indexAction($request, $translator, $surveyQuestion);
    }
    /**
     * @Route("/{id}", name="organization_survey_question_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        TranslatorInterface $translator,
        SurveyQuestion $surveyQuestion
    ) {
        $em = $this->getDoctrine()->getManager();

        $survey = $surveyQuestion->getSurvey();
        $this->denyAccessUnlessGranted(SurveyVoter::MANAGE, $survey);

        $locked = $survey->getAnswers()->count() > 0;
        $form = $this->createForm(SurveyQuestionType::class, $surveyQuestion, [
            'locked' => $locked
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'survey_question'));
                return $this->redirectToRoute('organization_survey_question_list', ['id' => $survey->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.save_error', [], 'survey_question'));
            }
        }

        $title = $translator->trans(
            $surveyQuestion->getId() ? 'title.edit' : 'title.new',
            [],
            'survey_question'
        );

        $breadcrumb = [
            [
                'fixed' => $survey->getTitle(),
                'routeName' => 'organization_survey_question_list',
                'routeParams' => ['id' => $survey->getId()]
            ],
            $surveyQuestion->getId() ?
                ['fixed' => $translator->trans('title.edit', [], 'survey_question')] :
                ['fixed' => $translator->trans('title.new', [], 'survey_question')]
        ];

        return $this->render('organization/survey/question_form.html.twig', [
            'menu_path' => 'organization_survey_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView(),
            'survey' => $survey,
            'locked' => $locked
        ]);
    }

    /**
     * @Route("/listar/{id}/resultado/{page}", name="organization_survey_question_list", requirements={"page" = "\d+"},
     *     defaults={"page" = 1},  methods={"GET"})
     */
    public function listAction(
        Request $request,
        TranslatorInterface $translator,
        Survey $survey,
        $page = 1
    ) {
        $this->denyAccessUnlessGranted(SurveyVoter::MANAGE, $survey);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('sq')
            ->from(SurveyQuestion::class, 'sq')
            ->orderBy('sq.orderNr');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('sq.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('sq.survey = :survey')
            ->setParameter('survey', $survey);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager->setCurrentPage($page);
        } catch (\PagerFanta\Exception\OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'survey_question') . ' - ' . $survey->getTitle();

        $breadcrumb = [
            [
                'fixed' => $survey->getTitle()
            ]
        ];

        return $this->render('organization/survey/question_list.html.twig', [
            'menu_path' => 'organization_survey_list',
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'survey_question',
            'survey' => $survey,
            'locked' => $survey->getAnswers()->count() > 0
        ]);
    }

    /**
     * @Route("/operacion/{id}", name="organization_survey_question_operation", methods={"POST"})
     */
    public function operationAction(
        Request $request,
        SurveyQuestionRepository $surveyQuestionRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        Survey $survey
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(SurveyVoter::MANAGE, $survey);

        if ($request->request->has('up')) {
            return $this->processUp($request, $surveyQuestionRepository, $survey);
        }

        if ($request->request->has('down')) {
            return $this->processDown($request, $surveyQuestionRepository, $survey);
        }

        $items = $request->request->get('items', []);
        if (count($items) === 0 || $survey->getAnswers()->count() > 0) {
            return $this->redirectToRoute('organization_survey_question_list', ['id' => $survey->getId()]);
        }

        // borrar preguntas
        return $this->processDelete($request, $surveyQuestionRepository, $translator, $items, $organization, $survey);
    }

    /**
     * @param Request $request
     * @param SurveyQuestionRepository $surveyQuestionRepository
     * @param TranslatorInterface $translator,
     * @param $items
     * @param Organization $organization
     * @param Survey $survey
     * @return Response
     */
    private function processDelete(
        Request $request,
        SurveyQuestionRepository $surveyQuestionRepository,
        TranslatorInterface $translator,
        $items,
        Organization $organization,
        Survey $survey
    ) {
        $em = $this->getDoctrine()->getManager();

        $surveys = $surveyQuestionRepository->findAllInListByIdAndOrganization($items, $organization);
        if (count($surveys) === 0) {
            return $this->redirectToRoute('organization_survey_question_list', ['id' => $survey->getId()]);
        }

        if ($request->get('confirm', '') === 'ok') {
            try {
                $surveyQuestionRepository->deleteFromList($surveys);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'survey_question'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'survey_question'));
            }
            return $this->redirectToRoute('organization_survey_question_list', ['id' => $survey->getId()]);
        }

        return $this->render('organization/survey/question_delete.html.twig', [
            'menu_path' => 'organization_survey_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'survey_question')]],
            'title' => $translator->trans('title.delete', [], 'survey_question'),
            'items' => $surveys
        ]);
    }

    private function processUp(Request $request, SurveyQuestionRepository $surveyQuestionRepository, Survey $survey)
    {
        $surveyQuestion = $surveyQuestionRepository->find($request->request->get('up'));
        if ($surveyQuestion && $surveyQuestion->getSurvey() === $survey) {
            $previousSurveyQuestion = $surveyQuestionRepository->getPreviousQuestion($surveyQuestion);
            if ($previousSurveyQuestion) {
                $temp = $previousSurveyQuestion->getOrderNr();
                $previousSurveyQuestion->setOrderNr($surveyQuestion->getOrderNr());
                $surveyQuestion->setOrderNr($temp);
                try {
                    $this->getDoctrine()->getManager()->flush();
                } catch (\Exception $e) {
                }
            }
        }
        return $this->redirectToRoute('organization_survey_question_list', ['id' => $survey->getId()]);
    }

    private function processDown(Request $request, SurveyQuestionRepository $surveyQuestionRepository, Survey $survey)
    {
        $surveyQuestion = $surveyQuestionRepository->find($request->request->get('down'));
        if ($surveyQuestion && $surveyQuestion->getSurvey() === $survey) {
            $nextSurveyQuestion = $surveyQuestionRepository->getNextQuestion($surveyQuestion);
            if ($nextSurveyQuestion) {
                $temp = $nextSurveyQuestion->getOrderNr();
                $nextSurveyQuestion->setOrderNr($surveyQuestion->getOrderNr());
                $surveyQuestion->setOrderNr($temp);
                try {
                    $this->getDoctrine()->getManager()->flush();
                } catch (\Exception $e) {
                }
            }
        }
        return $this->redirectToRoute('organization_survey_question_list', ['id' => $survey->getId()]);
    }
}
