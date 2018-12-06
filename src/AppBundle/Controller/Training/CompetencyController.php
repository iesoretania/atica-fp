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

use AppBundle\Entity\Edu\Competency;
use AppBundle\Entity\Edu\Training;
use AppBundle\Form\Type\Edu\CompetencyType;
use AppBundle\Repository\Edu\CompetencyRepository;
use AppBundle\Security\Edu\TrainingVoter;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/ensenanza")
 */
class CompetencyController extends Controller
{
    /**
     * @Route("/{id}/competencia/nueva", name="training_competency_new", methods={"GET", "POST"})
     **/
    public function newAction(Request $request, TranslatorInterface $translator, Training $training)
    {
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $competency = new Competency();
        $competency
            ->setTraining($training);

        $this->getDoctrine()->getManager()->persist($competency);

        return $this->formAction($request, $translator, $competency);
    }

    /**
     * @Route("/competencia/{id}", name="training_competency_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function formAction(
        Request $request,
        TranslatorInterface $translator,
        Competency $competency
    ) {
        $training = $competency->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $form = $this->createForm(CompetencyType::class, $competency, [
            'training' => $training
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_competency'));
                return $this->redirectToRoute('training_competency_list', [
                    'id' => $training->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_competency'));
            }
        }

        $title = $translator->trans(
            $competency->getId() ? 'title.edit' : 'title.new',
            [],
            'edu_competency'
        );

        $breadcrumb = [
            [
                'fixed' => $training->getName(),
                'routeName' => 'training_competency_list',
                'routeParams' => ['id' => $training->getId()]
            ],
            [
                'fixed' => $translator->trans('title.list', [], 'edu_competency'),
                'routeName' => 'training_competency_list',
                'routeParams' => ['id' => $training->getId()]
            ],
            $competency->getId() ?
                ['fixed' => $competency->getCode()] :
                ['fixed' => $this->get('translator')->trans('title.new', [], 'edu_competency')]
        ];

        return $this->render('training/competency_form.html.twig', [
            'menu_path' => 'training',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'competency' => $competency,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/competencial/{page}/", name="training_competency_list",
     *     requirements={"id" = "\d+", "page" = "\d+"}, defaults={"page" = 1}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        TranslatorInterface $translator,
        Training $training,
        $page = 1
    ) {
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('c')
            ->from(Competency::class, 'c')
            ->orderBy('c.code');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('c.code LIKE :tq')
                ->orWhere('c.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('c.training = :training')
            ->setParameter('training', $training);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $training->getName() . ' - ' . $translator->trans('title.list', [], 'edu_competency');

        $breadcrumb = [
            [
                'fixed' => $training->getName()
            ],
            ['fixed' => $translator->trans('title.list', [], 'edu_competency')]
        ];

        return $this->render('training/competency_list.html.twig', [
            'menu_path' => 'training',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'training' => $training,
            'domain' => 'edu_competency'
        ]);
    }

    /**
     * @Route("/competencia/eliminar/{id}", name="training_competency_delete",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        CompetencyRepository $competencyRepository,
        TranslatorInterface $translator,
        Training $training
    ) {
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('training_competency_list', ['id' => $training->getId()]);
        }

        $competencies = $competencyRepository->findAllInListByIdAndTraining($items, $training);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $competencyRepository->deleteFromList($competencies);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_competency'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'edu_competency'));
            }
            return $this->redirectToRoute('training_competency_list', ['id' => $training->getId()]);
        }

        $breadcrumb = [
            [
                'fixed' => $training->getName(),
                'routeName' => 'training_competency_list',
                'routeParams' => ['id' => $training->getId()]
            ],
            ['fixed' => $this->get('translator')->trans('title.delete', [], 'edu_competency')]
        ];

        return $this->render('training/competency_delete.html.twig', [
            'menu_path' => 'training',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'edu_competency'),
            'competencies' => $competencies
        ]);
    }

    /**
     * @Route("/competencia/importar/{id}", name="training_competency_import",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function importAction(
        Request $request,
        CompetencyRepository $competencyRepository,
        TranslatorInterface $translator,
        Training $training
    ) {
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $em = $this->getDoctrine()->getManager();

        $lines = trim($request->request->get('data', []));
        if ($lines === '') {
            return $this->redirectToRoute('training_competency_list', ['id' => $training->getId()]);
        }

        $items = $this->parseImport($lines);
        foreach ($items as $code => $item) {
            if (null === $competencyRepository->findOneByCodeAndTraining($code, $training)) {
                $competency = new Competency();
                $competency
                    ->setTraining($training)
                    ->setCode($code)
                    ->setDescription($item);
                $em->persist($competency);
            }
        }
        //try {
            $em->flush();
            $this->addFlash('success', $translator->trans('message.saved', [], 'edu_competency'));
        //} catch (\Exception $e) {
        //    $this->addFlash('error', $translator->trans('message.error', [], 'edu_competency'));
        //}

        return $this->redirectToRoute('training_competency_list', ['id' => $training->getId()]);
    }

    /**
     * @param $lines
     *
     * @return array
     */
    private function parseImport($lines)
    {
        $items = explode("\n", $lines);
        $output = [];
        $matches = [];

        foreach ($items as $item) {
            preg_match('/^(.{1,10})\) (.*)/u', $item, $matches);
            if ($matches) {
                $output[$matches[1]] = $matches[2];
            }
        }

        return $output;
    }
}
