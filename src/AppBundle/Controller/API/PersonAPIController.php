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

namespace AppBundle\Controller\API;

use AppBundle\Entity\Person;
use AppBundle\Entity\User;
use AppBundle\Form\Type\NewPersonType;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PersonAPIController extends Controller
{
    /**
     * @Route("/api/persona/crear", name="api_person_new", methods={"GET", "POST"})
     */
    public function apiNewPersonAction(Request $request, UserExtensionService $userExtensionService)
    {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_COMPANIES, $organization);

        $em = $this->getDoctrine()->getManager();

        $newPerson = new Person();

        $form = $this->createForm(NewPersonType::class, $newPerson, [
            'action' => $this->generateUrl('api_person_new'),
            'method' => 'POST'
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newUser = new User();
            $newUser
                ->setPerson($newPerson)
                ->setLoginUsername($newPerson->getUniqueIdentifier());
            $emailAddress = $form->get('userEmailAddress')->getData();
            if ($emailAddress) {
                $newUser
                    ->setEmailAddress($emailAddress);
            }
            $em->persist($newUser);
            $em->persist($newPerson);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'id' => $newPerson->getId(),
                'name' => $newPerson->getFullDisplayName()
            ]);
        }

        return $this->render('user/person_simple_form.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
