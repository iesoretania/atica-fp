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

namespace AppBundle\Controller\Organization\Import;

use AppBundle\Entity\Membership;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Person;
use AppBundle\Entity\User;
use AppBundle\Form\Model\TeacherImport;
use AppBundle\Form\Type\Import\TeacherImportType;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use AppBundle\Utils\CsvImporter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;

class TeacherImportController extends Controller
{
    /**
     * @Route("/centro/importar/profesorado", name="organization_import_teacher_form", methods={"GET", "POST"})
     */
    public function indexAction(UserExtensionService $userExtensionService, Request $request)
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $formData = new TeacherImport();
        $form = $this->createForm(TeacherImportType::class, $formData);
        $form->handleRequest($request);

        $stats = null;
        $breadcrumb = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $stats = $this->importTeachersFromCsv($formData->getFile()->getPathname(), $organization, [
                'generate_password' => $formData->getGeneratePassword(),
                'external_check' => $formData->isExternalPassword()
            ]);

            if (null !== $stats) {
                $this->addFlash('success', $this->get('translator')->trans('message.import_ok', [], 'import'));
                $breadcrumb[] = ['fixed' => $this->get('translator')->trans('title.import_result', [], 'import')];
            } else {
                $this->addFlash('error', $this->get('translator')->trans('message.import_error', [], 'import'));
            }
        }
        $title = $this->get('translator')->trans('title.teacher.import', [], 'import');

        return $this->render('admin/organization/import/teacher_import_form.html.twig', [
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'generate_password' => $formData->getGeneratePassword(),
            'stats' => $stats
        ]);
    }

    /**
     * @param string $file
     * @param Organization $organization
     * @param array $options
     * @return array|null
     */
    private function importTeachersFromCsv($file, Organization $organization, $options = [])
    {
        $generatePassword = isset($options['generate_password']) && $options['generate_password'];
        $external = isset($options['external_check']) && $options['external_check'];
        $newUserCount = 0;
        $newMemberships = 0;
        $existingUsers = 0;
        $existingMemberships = 0;

        $em = $this->getDoctrine()->getManager();

        $importer = new CsvImporter($file, true);
        $encoder = $this->container->get('security.password_encoder');

        $userCollection = [];
        $newUserCollection = [];

        try {
            while ($data = $importer->get(100)) {
                foreach ($data as $userData) {
                    if (!isset($userData['Usuario IdEA'])) {
                        return null;
                    }
                    $userName = $userData['Usuario IdEA'];

                    $user = $em->getRepository('AppBundle:User')->findOneBy(['loginUsername' => $userName]);
                    $alreadyProcessed = isset($userCollection[$userName]);

                    if (null === $user) {
                        if ($alreadyProcessed) {
                            $user = $userCollection[$userName];
                        } else {
                            $user = new User();
                            $person = new Person();
                            $user->setPerson($person);

                            $fullName = explode(', ', $userData['Empleado/a']);

                            $person
                                ->setFirstName($fullName[1])
                                ->setLastName($fullName[0])
                                ->setGender(User::GENDER_NEUTRAL)
                                ->setInternalCode($userData['Empleado/a']);

                            $user
                                ->setLoginUsername($userName)
                                ->setEnabled(true)
                                ->setGlobalAdministrator(false)
                                ->setAllowExternalCheck($external)
                                ->setExternalCheck($external);

                            if ($generatePassword) {
                                $user
                                    ->setPassword($encoder->encodePassword($user, $userData['Usuario IdEA']));
                            }

                            $em->persist($person);
                            $em->persist($user);

                            $userCollection[$userName] = $user;
                            $newUserCollection[$userName] = $user;

                            $newUserCount++;
                        }
                    } else {
                        if (!$alreadyProcessed) {
                            $existingUsers++;
                            $userCollection[$userName] = $user;
                        }
                    }

                    $validFrom = \DateTime::createFromFormat(
                        'd/m/Y H:i:s',
                        $userData['Fecha de toma de posesión'] . '00:00:00'
                    );
                    $validUntil = ($userData['Fecha de cese']) ?
                        \DateTime::createFromFormat('d/m/Y H:i:s', $userData['Fecha de cese'] . '23:59:59') :
                        null;

                    if (false === $validFrom) {
                        continue;
                    }

                    /** @var Membership $membership */
                    $membership = $em->getRepository('AppBundle:Membership')->findOneBy([
                        'organization' => $organization,
                        'user' => $user,
                        'validFrom' => $validFrom
                    ]);

                    if (null === $membership) {
                        $membership = new Membership();
                        $membership
                            ->setOrganization($organization)
                            ->setUser($user)
                            ->setValidFrom($validFrom)
                            ->setValidUntil($validUntil);

                        $em->persist($membership);

                        $newMemberships++;
                    } else {
                        $membership
                            ->setValidUntil($validUntil);

                        $existingMemberships++;
                    }
                }
            }
            $em->flush();
        } catch (Exception $e) {
            return null;
        }

        return [
            'new_user_count' => $newUserCount,
            'new_membership_count' => $newMemberships,
            'existing_user_count' => $existingUsers,
            'existing_membership_count' => $existingMemberships,
            'user_collection' => $newUserCollection
        ];
    }
}
