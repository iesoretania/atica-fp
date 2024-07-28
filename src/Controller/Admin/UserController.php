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

namespace App\Controller\Admin;

use App\Entity\Person;
use App\Form\Type\PersonType;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/admin/usuarios")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/nuevo", name="admin_user_form_new", methods={"GET", "POST"})
     * @Route("/{id}", name="admin_user_form_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        TranslatorInterface $translator,
        UserPasswordHasherInterface $passwordEncoder,
        Person $localUser = null
    ): Response {
        $em = $this->getDoctrine()->getManager();

        if (null === $localUser) {
            $localUser = new Person();
            $em->persist($localUser);
        }

        $form = $this->createForm(PersonType::class, $localUser, [
            'own' => $this->getUser()->getId() === $localUser->getId(),
            'admin' => $this->getUser()->isGlobalAdministrator(),
            'new' => $localUser->getId() === null
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message = $this->processPasswordChange($localUser, $translator, $form, $passwordEncoder);

            try {
                $em->flush();
                $this->addFlash('success', $message);
                return $this->redirectToRoute('admin_user_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'user'));
            }
        }

        $title = $translator->trans($localUser->getId() !== null ? 'title.edit' : 'title.new', [], 'user');

        $breadcrumb = [
            $localUser->getId() !== null ?
                ['fixed' => (string)$localUser] :
                ['fixed' => $translator->trans('title.new', [], 'user')]
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
    public function listAction(Request $request, TranslatorInterface $translator, $page = 1): Response
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('p')
            ->from(Person::class, 'p')
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('p.id = :q')
                ->orWhere('p.loginUsername LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.emailAddress LIKE :tq')
                ->setParameter('tq', '%' . $q . '%')
                ->setParameter('q', $q);
        }

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'user');

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
    public function deleteAction(Request $request, TranslatorInterface $translator): Response
    {
        $em = $this->getDoctrine()->getManager();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->createQueryBuilder();

        $items = $request->request->get('users', []);
        if ((is_array($items) || $items instanceof \Countable ? count($items) : 0) === 0) {
            return $this->redirectToRoute('admin_user_list');
        }

        $users = $queryBuilder
            ->select('p')
            ->from(Person::class, 'p')
            ->where('p.id IN (:items)')
            ->andWhere('p.id != :current')
            ->setParameter('items', $items)
            ->setParameter('current', $this->getUser()->getId())
            ->orderBy('p.firstName')
            ->addOrderBy('p.lastName')
            ->getQuery()
            ->getResult();

        if ($request->get('confirm', '') === 'ok') {
            try {
                $em->createQueryBuilder()
                    ->delete(Person::class, 'p')
                    ->where('p IN (:items)')
                    ->setParameter('items', $items)
                    ->getQuery()
                    ->execute();

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'user'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'user'));
            }
            return $this->redirectToRoute('admin_user_list');
        }

        return $this->render('admin/user/delete.html.twig', [
            'menu_path' => 'admin_user_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'user')]],
            'title' => $translator->trans('title.delete', [], 'user'),
            'users' => $users
        ]);
    }

    /**
     * @param Person $user
     * @param TranslatorInterface $translator
     * @param FormInterface $form
     * @param UserPasswordHasherInterface $passwordEncoder
     * @return string
     */
    private function processPasswordChange(
        Person $user,
        TranslatorInterface $translator,
        FormInterface $form,
        UserPasswordHasherInterface $passwordEncoder
    ): string {
        // Si es solicitado, cambiar la contraseña
        $passwordSubmit = $form->get('changePassword');
        if (($passwordSubmit instanceof SubmitButton) && $passwordSubmit->isClicked()) {
            $user->setPassword(
                $passwordEncoder
                    ->hashPassword($user, $form->get('newPassword')->get('first')->getData())
            );
            $message = $translator->trans('message.password_changed', [], 'user');
        } else {
            $message = $translator->trans('message.saved', [], 'user');
        }
        return $message;
    }
}
