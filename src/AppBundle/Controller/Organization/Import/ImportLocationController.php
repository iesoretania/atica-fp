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

use AppBundle\Entity\ICT\Location;
use AppBundle\Entity\Organization;
use AppBundle\Form\Model\LocationImport;
use AppBundle\Form\Type\Import\LocationType;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use AppBundle\Utils\CsvImporter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;

class ImportLocationController extends Controller
{
    /**
     * @Route("/centro/importar/dependencias", name="organization_import_location_form", methods={"GET", "POST"})
     */
    public function indexAction(UserExtensionService $userExtensionService, Request $request)
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $formData = new LocationImport();
        $form = $this->createForm(LocationType::class, $formData);
        $form->handleRequest($request);

        $stats = null;
        $breadcrumb = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $stats = $this->importLocationsFromCsv($formData->getFile()->getPathname(), $organization, [
                'only_keep_new' => $formData->getOnlyKeepNew()
            ]);

            if (null !== $stats) {
                $this->addFlash('success', $this->get('translator')->trans('message.import_ok', [], 'import'));
                $breadcrumb[] = ['fixed' => $this->get('translator')->trans('title.import_result', [], 'import')];
            } else {
                $this->addFlash('error', $this->get('translator')->trans('message.import_error', [], 'import'));
            }
        }
        $title = $this->get('translator')->trans('title.location.import', [], 'import');

        return $this->render('admin/organization/import/location_form.html.twig', [
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'stats' => $stats
        ]);
    }

    /**
     * @param string $file
     * @param Organization $organization
     * @param array $options
     * @return array|null
     */
    private function importLocationsFromCsv($file, Organization $organization, $options = [])
    {
        $onlyKeepNew = isset($options['only_keep_new']) && $options['only_keep_new'];
        $newCount = 0;
        $updatedCount = 0;

        $em = $this->getDoctrine()->getManager();

        $importer = new CsvImporter($file, true);

        $collection = [];
        $newCollection = [];

        try {
            while ($data = $importer->get(100)) {
                foreach ($data as $userData) {
                    if (!isset($userData['Dependencias'])) {
                        return null;
                    }
                    $name = $userData['Dependencias'];

                    $item = $em->getRepository('AppBundle:ICT\Location')->
                        findOneBy(['name' => $name, 'organization' => $organization]);

                    $alreadyProcessed = isset($collection[$name]);

                    if (null === $item) {
                        if ($alreadyProcessed) {
                            $item = $collection[$name];
                        } else {
                            $item = new Location();

                            $em->persist($item);

                            $collection[$name] = $item;
                            $newCollection[$name] = $item;

                            $newCount++;
                        }
                    } else {
                        if (!$alreadyProcessed) {
                            $updatedCount++;
                        }
                    }

                    $item
                        ->setOrganization($organization)
                        ->setName($name)
                        ->setAdditionalData($userData['Plantas'] ?: null);
                }
            }
            $em->flush();
        } catch (Exception $e) {
            return null;
        }

        return [
            'new_count' => $newCount,
            'updated_count' => $updatedCount,
            'new_collection' => $newCollection
        ];
    }
}
