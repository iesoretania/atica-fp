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

namespace App\Controller\API;

use App\Entity\Person;
use App\Form\Type\NewPersonType;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class PersonAPIController extends AbstractController
{
    /**
     * @Route("/api/persona/crear", name="api_person_new", methods={"GET", "POST"})
     */
    public function apiNewPersonAction(
        Request $request,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry,
        UserPasswordHasherInterface $passwordEncoder
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_COMPANIES, $organization);

        $em = $managerRegistry->getManager();

        $newPerson = new Person();

        $form = $this->createForm(NewPersonType::class, $newPerson, [
            'action' => $this->generateUrl('api_person_new'),
            'method' => 'POST'
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uid = $form->get('uniqueIdentifier')->getData();
            $newPerson->setPassword($passwordEncoder->hashPassword($newPerson, $uid));
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
