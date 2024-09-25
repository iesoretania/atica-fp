<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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

namespace App\Controller\Organization;

use App\Entity\Organization;
use App\Entity\Survey;
use App\Entity\SurveyQuestion;
use App\Form\Type\SurveyQuestionType;
use App\Repository\SurveyQuestionRepository;
use App\Security\SurveyVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/centro/encuesta/pregunta')]
class SurveyQuestionController extends AbstractController
{
    #[Route(path: '/nueva/{id}', name: 'organization_survey_question_new', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TranslatorInterface $translator,
        SurveyQuestionRepository $surveyQuestionRepository,
        ManagerRegistry $managerRegistry,
        Survey $survey
    ): Response
    {
        $this->denyAccessUnlessGranted(SurveyVoter::MANAGE, $survey);

        if ($survey->getAnswers()->count() > 0) {
            return $this->redirectToRoute('organization_survey_question_list', ['id' => $survey->getId()]);
        }

        $surveyQuestion = new SurveyQuestion();
        $lastOrderNr = $surveyQuestionRepository->getLastOrderNr($survey);
        $surveyQuestion
            ->setSurvey($survey)
            ->setOrderNr($lastOrderNr + 1);

        $managerRegistry->getManager()->persist($surveyQuestion);

        return $this->index($request, $translator, $managerRegistry, $surveyQuestion);
    }
    #[Route(path: '/{id}', name: 'organization_survey_question_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        SurveyQuestion $surveyQuestion
    ): Response
    {
        $em = $managerRegistry->getManager();

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
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_survey_question'));
                return $this->redirectToRoute('organization_survey_question_list', ['id' => $survey->getId()]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.save_error', [], 'edu_survey_question'));
            }
        }

        $title = $translator->trans(
            $surveyQuestion->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'edu_survey_question'
        );

        $breadcrumb = [
            [
                'fixed' => $survey->getTitle(),
                'routeName' => 'organization_survey_question_list',
                'routeParams' => ['id' => $survey->getId()]
            ],
            $surveyQuestion->getId() !== null ?
                ['fixed' => $translator->trans('title.edit', [], 'edu_survey_question')] :
                ['fixed' => $translator->trans('title.new', [], 'edu_survey_question')]
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

    #[Route(path: '/listar/{id}/resultado/{page}', name: 'organization_survey_question_list', requirements: ['page' => '\d+'], defaults: ['page' => 1], methods: ['GET'])]
    public function list(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Survey $survey,
        int $page = 1
    ): Response
    {
        $this->denyAccessUnlessGranted(SurveyVoter::MANAGE, $survey);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('sq')
            ->from(SurveyQuestion::class, 'sq')
            ->orderBy('sq.orderNr');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('sq.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('sq.survey = :survey')
            ->setParameter('survey', $survey);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'edu_survey_question') . ' - ' . $survey->getTitle();

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
            'domain' => 'edu_survey_question',
            'survey' => $survey,
            'locked' => $survey->getAnswers()->count() > 0
        ]);
    }

    #[Route(path: '/operacion/{id}', name: 'organization_survey_question_operation', methods: ['POST'])]
    public function operation(
        Request $request,
        SurveyQuestionRepository $surveyQuestionRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Survey $survey
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(SurveyVoter::MANAGE, $survey);

        if ($request->request->has('up')) {
            return $this->processUp($request, $surveyQuestionRepository, $managerRegistry, $survey);
        }

        if ($request->request->has('down')) {
            return $this->processDown($request, $surveyQuestionRepository, $managerRegistry, $survey);
        }

        $items = $request->request->all('items');
        if ((is_countable($items) ? count($items) : 0) === 0 || $survey->getAnswers()->count() > 0) {
            return $this->redirectToRoute('organization_survey_question_list', ['id' => $survey->getId()]);
        }

        // borrar preguntas
        return $this->processDelete($request, $surveyQuestionRepository, $translator, $managerRegistry, $items, $organization, $survey);
    }

    private function processDelete(
        Request $request,
        SurveyQuestionRepository $surveyQuestionRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        $items,
        Organization $organization,
        Survey $survey
    ): Response
    {
        $em = $managerRegistry->getManager();

        $surveys = $surveyQuestionRepository->findAllInListByIdAndOrganization($items, $organization);
        if ($surveys === []) {
            return $this->redirectToRoute('organization_survey_question_list', ['id' => $survey->getId()]);
        }

        if ($request->get('confirm', '') === 'ok') {
            try {
                $surveyQuestionRepository->deleteFromList($surveys);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_survey_question'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'edu_survey_question'));
            }
            return $this->redirectToRoute('organization_survey_question_list', ['id' => $survey->getId()]);
        }

        return $this->render('organization/survey/question_delete.html.twig', [
            'menu_path' => 'organization_survey_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'edu_survey_question')]],
            'title' => $translator->trans('title.delete', [], 'edu_survey_question'),
            'items' => $surveys
        ]);
    }

    private function processUp(
        Request $request,
        SurveyQuestionRepository $surveyQuestionRepository,
        ManagerRegistry $managerRegistry,
        Survey $survey
    ): Response {
        /** @var SurveyQuestion $surveyQuestion */
        $surveyQuestion = $surveyQuestionRepository->find($request->request->get('up'));
        if ($surveyQuestion && $surveyQuestion->getSurvey() === $survey) {
            $previousSurveyQuestion = $surveyQuestionRepository->getPreviousQuestion($surveyQuestion);
            if ($previousSurveyQuestion !== null) {
                $temp = $previousSurveyQuestion->getOrderNr();
                $previousSurveyQuestion->setOrderNr($surveyQuestion->getOrderNr());
                $surveyQuestion->setOrderNr($temp);
                try {
                    $managerRegistry->getManager()->flush();
                } catch (\Exception) {
                }
            }
        }
        return $this->redirectToRoute('organization_survey_question_list', ['id' => $survey->getId()]);
    }

    private function processDown(
        Request $request,
        SurveyQuestionRepository $surveyQuestionRepository,
        ManagerRegistry $managerRegistry,
        Survey $survey
    ): Response {
        $surveyQuestion = $surveyQuestionRepository->find($request->request->get('down'));
        if ($surveyQuestion && $surveyQuestion->getSurvey() === $survey) {
            $nextSurveyQuestion = $surveyQuestionRepository->getNextQuestion($surveyQuestion);
            if ($nextSurveyQuestion !== null) {
                $temp = $nextSurveyQuestion->getOrderNr();
                $nextSurveyQuestion->setOrderNr($surveyQuestion->getOrderNr());
                $surveyQuestion->setOrderNr($temp);
                try {
                    $managerRegistry->getManager()->flush();
                } catch (\Exception) {
                }
            }
        }
        return $this->redirectToRoute('organization_survey_question_list', ['id' => $survey->getId()]);
    }
}
