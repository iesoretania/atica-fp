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

namespace App\Listener;

use App\Entity\Person;
use App\Repository\OrganizationRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class SecurityListener implements EventSubscriberInterface
{
    private RequestStack $requestStack;

    private ManagerRegistry $doctrine;

    private OrganizationRepository $organizationRepository;


    public function __construct(
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        OrganizationRepository $organizationRepository
    ) {
        $this->requestStack = $requestStack;
        $this->doctrine = $doctrine;
        $this->organizationRepository = $organizationRepository;
    }

    final public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        dump("onSecurityInteractiveLogin");
        /** @var Person $user */
        $user = $event->getAuthenticationToken()->getUser();

        $em = $this->doctrine->getManager();

        $user->setLastAccess(new \DateTime());
        $em->flush();

        // comprobar si es administrador global y, en ese caso, devolver todas las organizaciones
        if ($user->isGlobalAdministrator()) {
            $organizationsCount = $this->organizationRepository->countOrganizationsByPerson($user);

            if ($organizationsCount > 1) {
                $this->requestStack->getSession()->set(
                    '_security.organization.target_path',
                    $this->requestStack->getSession()->get('_security.main.target_path')
                );
            } else {
                $organization = $this->organizationRepository->findFirstByUserOrNull($user);
                $this->requestStack->getSession()->set('organization_id', $organization->getId());
            }

            return;
        }

        // no es administrador global, consultar las pertenencias activas
        $organizationsCount = $this->organizationRepository->countOrganizationsByPerson($user);

        switch ($organizationsCount) {
            case 0:
                throw new CustomUserMessageAuthenticationException('form.login.error.no_membership');
            case 1:
                $organization = $this->organizationRepository->findFirstByUserOrNull($user);
                $this->requestStack->getSession()->set('organization_id', $organization->getId());
                break;
            default:
                $this->requestStack->getSession()->set(
                    '_security.organization.target_path',
                    $this->requestStack->getSession()->get('_security.main.target_path')
                );
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin'];
    }
}
