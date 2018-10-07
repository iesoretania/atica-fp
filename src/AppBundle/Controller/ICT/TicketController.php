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

use AppBundle\Entity\ICT\Ticket;
use AppBundle\Form\Type\ICT\TicketType;
use AppBundle\Service\UserExtensionService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class TicketController extends Controller
{
    /**
     * @Route("/tic/incidencia/nueva", name="ict_ticket_new", methods={"GET", "POST"})
     */
    public function ictTicketFormAction(
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        Request $request
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $ticket = new Ticket();
        $ticket
            ->setOrganization($organization)
            ->setCreatedBy($this->getUser());

        $em = $this->getDoctrine()->getManager();
        $em->persist($ticket);

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();

                $this->addFlash('success', $this->get('translator')->trans('message.saved', [], 'ict_ticket'));

                return $this->redirectToRoute('frontpage');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.save_error', [], 'ict_ticket'));
            }
        }

        $title = $translator->trans($ticket->getId() ? 'title.edit' : 'title.new', [], 'ict_ticket');

        return $this->render('ict/ticket/form.html.twig', [
            'menu_path' => 'ict_ticket_new',
            'title' => $title,
            'form' => $form->createView()
        ]);
    }
}
