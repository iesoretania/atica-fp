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

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\Person;
use AppBundle\Entity\User;
use AppBundle\Form\Type\UserType;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/usuarios")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class UserController extends Controller
{
    /**
     * @Route("/nuevo", name="admin_user_form_new", methods={"GET", "POST"})
     * @Route("/{id}", name="admin_user_form_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(Request $request, User $localUser = null)
    {
        $em = $this->getDoctrine()->getManager();

        if (null === $localUser) {
            $localPerson = new Person();
            $localUser = new User();
            $localPerson->setUser($localUser);
            $em->persist($localPerson);
            $em->persist($localUser);
        }

        $form = $this->createForm(UserType::class, $localUser, [
            'own' => $this->getUser()->getId() === $localUser->getId(),
            'admin' => $this->getUser()->isGlobalAdministrator(),
            'new' => $localUser->getId() === null
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message = $this->processPasswordChange($localUser, $form);

            try {
                $em->flush();
                $this->addFlash('success', $message);
                return $this->redirectToRoute('admin_user_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.error', [], 'user'));
            }
        }

        $title = $this->get('translator')->trans($localUser->getId() ? 'title.edit' : 'title.new', [], 'user');

        $breadcrumb = [
            $localUser->getId() ?
                ['fixed' => (string) $localUser] :
                ['fixed' => $this->get('translator')->trans('title.new', [], 'user')]
        ];

        return $this->render('admin/user/form.html.twig', [
            'menu_path' => 'admin_user_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView(),
            'user' => $localUser
        ]);
    }

    /**
     * @Route("/listar/{page}", name="admin_user_list", requirements={"page" = "\d+"},
     *     methods={"GET"})
     */
    public function listAction($page = 1, Request $request)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('u')
            ->from('AppBundle:User', 'u')
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->innerJoin('u.person', 'p');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('u.id = :q')
                ->orWhere('u.loginUsername LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('u.emailAddress LIKE :tq')
                ->setParameter('tq', '%'.$q.'%')
                ->setParameter('q', $q);
        }

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager->setCurrentPage($page);
        } catch (\PagerFanta\Exception\OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $this->get('translator')->trans('title.list', [], 'user');

        return $this->render('admin/user/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'user'
        ]);
    }

    /**
     * @Route("/eliminar", name="admin_user_delete", methods={"POST"})
     */
    public function deleteAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->createQueryBuilder();

        $items = $request->request->get('users', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('admin_user_list');
        }

        $users = $queryBuilder
            ->select('u')
            ->from('AppBundle:User', 'u')
            ->join('u.person', 'p')
            ->where('u.id IN (:items)')
            ->andWhere('u.id != :current')
            ->setParameter('items', $items)
            ->setParameter('current', $this->getUser()->getId())
            ->orderBy('p.firstName')
            ->addOrderBy('p.lastName')
            ->getQuery()
            ->getResult();

        if ($request->get('confirm', '') === 'ok') {
            try {
                /* Borrar primero las pertenencias */
                $em->createQueryBuilder()
                    ->delete('AppBundle:Membership', 'm')
                    ->where('m.user IN (:items)')
                    ->setParameter('items', $items)
                    ->getQuery()
                    ->execute();

                $em->createQueryBuilder()
                    ->delete('AppBundle:User', 'u')
                    ->where('u IN (:items)')
                    ->setParameter('items', $items)
                    ->getQuery()
                    ->execute();

                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.deleted', [], 'user'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.delete_error', [], 'user'));
            }
            return $this->redirectToRoute('admin_user_list');
        }

        return $this->render('admin/user/delete.html.twig', [
            'menu_path' => 'admin_user_list',
            'breadcrumb' => [['fixed' => $this->get('translator')->trans('title.delete', [], 'user')]],
            'title' => $this->get('translator')->trans('title.delete', [], 'user'),
            'users' => $users
        ]);
    }

    /**
     * @param User $user
     * @param FormInterface $form
     * @return string
     */
    private function processPasswordChange(User $user, FormInterface $form)
    {
        // Si es solicitado, cambiar la contraseña
        $passwordSubmit = $form->get('changePassword');
        if (($passwordSubmit instanceof SubmitButton) && $passwordSubmit->isClicked()) {
            $user->setPassword($this->container->get('security.password_encoder')
                ->encodePassword($user, $form->get('newPassword')->get('first')->getData()));
            $message = $this->get('translator')->trans('message.password_changed', [], 'user');
        } else {
            $message = $this->get('translator')->trans('message.saved', [], 'user');
        }
        return $message;
    }
}
