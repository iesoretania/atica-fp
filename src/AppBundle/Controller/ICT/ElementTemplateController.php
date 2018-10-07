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

use AppBundle\Entity\ICT\ElementTemplate;
use AppBundle\Form\Type\ICT\ElementTemplateType;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/tic/plantilla")
 */
class ElementTemplateController extends Controller
{
    /**
     * @Route("/nueva", name="ict_element_template_form_new", methods={"GET", "POST"})
     * @Route("/{id}", name="ict_element_template_form_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        UserExtensionService $userExtensionService,
        Request $request,
        ElementTemplate $elementTemplate = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        if (null === $elementTemplate) {
            $elementTemplate = new ElementTemplate();
            $elementTemplate
                ->setOrganization($organization);
            $em->persist($elementTemplate);
        }

        $form = $this->createForm(ElementTemplateType::class, $elementTemplate, [
            'new' => $elementTemplate->getId() === null
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash(
                    'success',
                    $this->get('translator')->trans('message.saved', [], 'ict_element_template')
                );
                return $this->redirectToRoute('ict_menu');
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    $this->get('translator')->trans('message.save_error', [], 'ict_element_template')
                );
            }
        }

        return $this->render('ict/element_template/form.html.twig', [
            'menu_path' => 'ict_element_template_form_new',
            'breadcrumb' => [
                ['fixed' => $elementTemplate->getId() ?
                    (string) $elementTemplate
                    : $this->get('translator')->trans('title.new', [], 'ict_element_template')]
            ],
            'title' => $this->get('translator')->trans($elementTemplate->getId() ?
                'title.edit' :
                'title.new', [], 'ict_element_template'),
            'form' => $form->createView()
        ]);
    }
}
