<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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

use App\Entity\Edu\Competency;
use App\Entity\Edu\Training;
use App\Form\Type\Edu\CompetencyType;
use App\Repository\Edu\CompetencyRepository;
use App\Security\Edu\TrainingVoter;
use App\Security\OrganizationVoter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/centro/ensenanza')]
class CompetencyController extends AbstractController
{
    #[Route(path: '/{id}/competencia/nueva', name: 'organization_training_competency_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Training $training
    ) {
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $training->getAcademicYear()->getOrganization());

        $competency = new Competency();
        $competency
            ->setTraining($training);

        $managerRegistry->getManager()->persist($competency);

        return $this->formAction($request, $translator, $managerRegistry, $competency);
    }

    #[Route(path: '/competencia/{id}', name: 'organization_training_competency_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function form(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Competency $competency
    ) {
        $training = $competency->getTraining();

        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $training->getAcademicYear()->getOrganization());

        $form = $this->createForm(CompetencyType::class, $competency, [
            'training' => $training
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $managerRegistry->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_competency'));
                return $this->redirectToRoute('organization_training_competency_list', [
                    'id' => $training->getId()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_competency'));
            }
        }

        $title = $translator->trans(
            $competency->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'edu_competency'
        );

        $breadcrumb = [
            [
                'fixed' => $training->getName(),
                'routeName' => 'organization_training_competency_list',
                'routeParams' => ['id' => $training->getId()]
            ],
            [
                'fixed' => $translator->trans('title.list', [], 'edu_competency'),
                'routeName' => 'organization_training_competency_list',
                'routeParams' => ['id' => $training->getId()]
            ],
            $competency->getId() !== null ?
                ['fixed' => $competency->getCode()] :
                ['fixed' => $translator->trans('title.new', [], 'edu_competency')]
        ];

        return $this->render('organization/training/competency_form.html.twig', [
            'menu_path' => 'organization_training_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'competency' => $competency,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/{id}/competencial/{page}/', name: 'organization_training_competency_list', requirements: ['id' => '\d+', 'page' => '\d+'], defaults: ['page' => 1], methods: ['GET'])]
    public function list(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Training $training,
        $page = 1
    ) {
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $training->getAcademicYear()->getOrganization());

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('c')
            ->from(Competency::class, 'c')
            ->orderBy('c.code');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('c.code LIKE :tq')
                ->orWhere('c.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('c.training = :training')
            ->setParameter('training', $training);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $training->getName() . ' - ' . $translator->trans('title.list', [], 'edu_competency');

        $breadcrumb = [
            [
                'fixed' => $training->getName()
            ],
            ['fixed' => $translator->trans('title.list', [], 'edu_competency')]
        ];

        return $this->render('organization/training/competency_list.html.twig', [
            'menu_path' => 'organization_training_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'training' => $training,
            'domain' => 'edu_competency'
        ]);
    }

    #[Route(path: '/competencia/eliminar/{id}', name: 'organization_training_competency_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        CompetencyRepository $competencyRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Training $training
    ) {
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $em = $managerRegistry->getManager();

        $items = $request->request->get('items', []);
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute('organization_training_competency_list', ['id' => $training->getId()]);
        }

        $competencies = $competencyRepository->findAllInListByIdAndTraining($items, $training);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $competencyRepository->deleteFromList($competencies);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_competency'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'edu_competency'));
            }
            return $this->redirectToRoute('organization_training_competency_list', ['id' => $training->getId()]);
        }

        $breadcrumb = [
            [
                'fixed' => $training->getName(),
                'routeName' => 'organization_training_competency_list',
                'routeParams' => ['id' => $training->getId()]
            ],
            ['fixed' => $translator->trans('title.delete', [], 'edu_competency')]
        ];

        return $this->render('organization/training/competency_delete.html.twig', [
            'menu_path' => 'organization_training_list',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'edu_competency'),
            'competencies' => $competencies
        ]);
    }

    #[Route(path: '/competencia/importar/{id}', name: 'organization_training_competency_import', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function import(
        Request $request,
        CompetencyRepository $competencyRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Training $training
    ) {
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $training->getAcademicYear()->getOrganization());

        $em = $managerRegistry->getManager();

        $lines = trim($request->request->get('data', []));
        if ($lines === '') {
            return $this->redirectToRoute('organization_training_competency_list', ['id' => $training->getId()]);
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
        try {
            $em->flush();
            $this->addFlash('success', $translator->trans('message.saved', [], 'edu_competency'));
        } catch (\Exception) {
            $this->addFlash('error', $translator->trans('message.error', [], 'edu_competency'));
        }
        return $this->redirectToRoute('organization_training_competency_list', ['id' => $training->getId()]);
    }


    #[Route(path: '/competencia/exportar/{id}', name: 'organization_training_competency_export', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function export(
        Training $training
    ) {
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $training->getAcademicYear()->getOrganization());

        $data = '';

        foreach ($training->getCompetencies() as $competency) {
            $lines = explode("\n", $competency->getDescription());
            foreach ($lines as &$line) {
                $line = trim($line);
            }
            $data .= $competency->getCode() . ') ' . implode(' ', $lines) . "\n";
        }

        return new Response(
            $data,
            Response::HTTP_OK,
            ['content-type' => 'text/plain']
        );
    }

    /**
     * @param $lines
     *
     * @return array
     */
    private function parseImport($lines)
    {
        $items = explode("\n", (string) $lines);
        $output = [];
        $matches = [];

        foreach ($items as $item) {
            preg_match('/^(.{1,10})\) (.*)/u', $item, $matches);
            if ($matches !== []) {
                $output[$matches[1]] = $matches[2];
            }
        }

        return $output;
    }
}
