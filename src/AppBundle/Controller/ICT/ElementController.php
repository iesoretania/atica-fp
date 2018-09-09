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

namespace AppBundle\Controller\ICT;

use AppBundle\Entity\ICT\Element;
use AppBundle\Entity\Location;
use AppBundle\Entity\Organization;
use AppBundle\Form\Type\ICT\ElementType;
use AppBundle\Security\ICT\ElementVoter;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/tic/equipamiento")
 */
class ElementController extends Controller
{
    /**
     * @Route("/nuevo", name="ict_element_form_new", methods={"GET", "POST"})
     * @Route("/{id}", name="ict_element_form_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(UserExtensionService $userExtensionService, Request $request, Element $element = null)
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);
        if ($element) {
            $this->denyAccessUnlessGranted(ElementVoter::MANAGE, $element);
        }

        $em = $this->getDoctrine()->getManager();

        if (null === $element) {
            $element = new Element();
            $element
                ->setOrganization($organization)
                ->setListedOn(new \DateTime());
            $em->persist($element);
        }

        $form = $this->createForm(ElementType::class, $element, [
            'new' => $element->getId() === null
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.saved', [], 'ict_element'));
                return $this->redirectToRoute('ict_menu');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.save_error', [], 'ict_element'));
            }
        }

        return $this->render('ict/element/form.html.twig', [
            'menu_path' => 'ict_element_form_new',
            'breadcrumb' => [['fixed' => $element->getId() ? (string) $element : $this->get('translator')->trans('title.new', [], 'ict_element')]],
            'title' => $this->get('translator')->trans($element->getId() ? 'title.edit' : 'title.new', [], 'ict_element'),
            'form' => $form->createView()
        ]);
    }
}
