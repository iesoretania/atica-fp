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

use AppBundle\Entity\Edu\Subject;
use AppBundle\Entity\Edu\Training;
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
class TrainingSubjectController extends Controller
{
    /**
     * @Route("/materia/{id}/{page}/", name="training_subject_list", requirements={"id" = "\d+", "page" = "\d+"},
     *     defaults={"page" = 1}, methods={"GET"})
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
            ->select('s')
            ->from(Subject::class, 's')
            ->join('s.grade', 'g')
            ->orderBy('g.name')
            ->addOrderBy('s.name');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('s.name LIKE :tq')
                ->orWhere('g.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('g.training = :training')
            ->setParameter('training', $training);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $translator->trans('title.list', [], 'edu_subject');

        $breadcrumb = [
            ['fixed' => $training->getName()],
            ['fixed' => $translator->trans('table.subjects', [], 'edu_training')]
        ];

        return $this->render('training/subject_list.html.twig', [
            'menu_path' => 'training_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_subject'
        ]);
    }
}
