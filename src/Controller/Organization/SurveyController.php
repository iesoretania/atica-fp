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
use App\Form\Type\SurveyType;
use App\Repository\SurveyQuestionRepository;
use App\Repository\SurveyRepository;
use App\Security\OrganizationVoter;
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

#[Route(path: '/centro/encuesta')]
class SurveyController extends AbstractController
{
    #[Route(path: '/nueva', name: 'organization_survey_new', methods: ['GET', 'POST'])]
    #[Route(path: '/{id}', name: 'organization_survey_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        SurveyRepository $surveyRepository,
        ManagerRegistry $managerRegistry,
        Survey $survey = null
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);
        if ($survey instanceof Survey) {
            $this->denyAccessUnlessGranted(SurveyVoter::MANAGE, $survey);
        }

        $em = $managerRegistry->getManager();

        if (!$survey instanceof Survey) {
            $survey = new Survey();
            $survey
                ->setOrganization($organization);
            $em->persist($survey);
            $surveys = $surveyRepository->findByOrganization($organization);
        } else {
            $surveys = [];
        }

        $form = $this->createForm(SurveyType::class, $survey, [
            'surveys' => $surveys
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Comprobar si es necesario copiar de otra encuesta
                if ($form->has('copyFrom') && $form->get('copyFrom')->getData()) {
                    /** @var Survey $original */
                    $original = $form->get('copyFrom')->getData();
                    foreach ($original->getQuestions() as $question) {
                        $newQuestion = new SurveyQuestion();
                        $newQuestion
                            ->setSurvey($survey)
                            ->setDescription($question->getDescription())
                            ->setItems($question->getItems())
                            ->setType($question->getType())
                            ->setMandatory($question->isMandatory())
                            ->setOrderNr($question->getOrderNr());
                        $em->persist($newQuestion);
                    }
                }
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_survey'));
                return $this->redirectToRoute('organization_survey_list');
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.save_error', [], 'edu_survey'));
            }
        }

        $title = $translator->trans(
            $survey->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'edu_survey'
        );

        $breadcrumb = [
            $survey->getId() !== null ?
                ['fixed' => $survey->getTitle()] :
                ['fixed' => $translator->trans('title.new', [], 'edu_survey')]
        ];

        return $this->render('organization/survey/form.html.twig', [
            'menu_path' => 'organization_survey_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/listar/{page}', name: 'organization_survey_list', requirements: ['page' => '\d+'], defaults: ['page' => 1], methods: ['GET'])]
    public function list(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        int $page = 1
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('s')
            ->from(Survey::class, 's')
            ->orderBy('s.title');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('s.title LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('s.organization = :organization')
            ->setParameter('organization', $organization);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'edu_survey');

        return $this->render('organization/survey/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_survey'
        ]);
    }

    #[Route(path: '/operacion', name: 'organization_survey_operation', methods: ['POST'])]
    public function operation(
        Request $request,
        SurveyRepository $surveyRepository,
        TranslatorInterface $translator,
        SurveyQuestionRepository $surveyQuestionRepository,
        ManagerRegistry $managerRegistry,
        UserExtensionService $userExtensionService
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $items = $request->request->all('items');
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute('organization_survey_list');
        }

        if ($request->request->has('purge')) {
            return $this->processPurge($request, $surveyRepository, $translator, $managerRegistry, $items, $organization);
        }

        // borrar encuestas
        return $this->processDelete(
            $request,
            $surveyRepository,
            $surveyQuestionRepository,
            $translator,
            $managerRegistry,
            $items,
            $organization
        );
    }

    /**
     * @param $items
     */
    private function processPurge(
        Request $request,
        SurveyRepository $surveyRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        $items,
        Organization $organization
    ): Response
    {
        $em = $managerRegistry->getManager();

        $surveys = $surveyRepository->findAllInListByIdAndOrganization($items, $organization);
        if ($surveys === []) {
            return $this->redirectToRoute('organization_survey_list');
        }
        if ($request->get('confirm', '') === 'ok') {
            try {
                $surveyRepository->purgeAnswersFromList($surveys);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.purged', [], 'edu_survey'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.purge_error', [], 'edu_survey'));
            }
            return $this->redirectToRoute('organization_survey_list');
        }

        return $this->render('organization/survey/purge.html.twig', [
            'menu_path' => 'organization_survey_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.purge', [], 'edu_survey')]],
            'title' => $translator->trans('title.purge', [], 'edu_survey'),
            'items' => $surveys
        ]);
    }

    /**
     * @param $items
     */
    private function processDelete(
        Request $request,
        SurveyRepository $surveyRepository,
        SurveyQuestionRepository $surveyQuestionRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        $items,
        Organization $organization
    ): Response
    {
        $em = $managerRegistry->getManager();

        $surveys = $surveyRepository->findAllInListByIdAndOrganizationAndNoAnswers($items, $organization);
        if ($surveys === []) {
            return $this->redirectToRoute('organization_survey_list');
        }

        if ($request->get('confirm', '') === 'ok') {
            try {
                foreach ($surveys as $survey) {
                    $surveyQuestionRepository->deleteFromSurvey($survey);
                }
                $surveyRepository->deleteFromList($surveys);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_survey'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'edu_survey'));
            }
            return $this->redirectToRoute('organization_survey_list');
        }

        return $this->render('organization/survey/delete.html.twig', [
            'menu_path' => 'organization_survey_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'edu_survey')]],
            'title' => $translator->trans('title.delete', [], 'edu_survey'),
            'items' => $surveys
        ]);
    }
}
