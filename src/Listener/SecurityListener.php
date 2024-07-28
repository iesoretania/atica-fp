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

use App\Entity\Organization;
use App\Entity\Person;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class SecurityListener implements EventSubscriberInterface
{
    private $session;
    private $doctrine;

    public function __construct(SessionInterface $session, ManagerRegistry $doctrine)
    {
        $this->session = $session;
        $this->doctrine = $doctrine;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        /** @var Person $user */
        $user = $event->getAuthenticationToken()->getUser();

        $em = $this->doctrine->getManager();

        $user->setLastAccess(new \DateTime());
        $em->flush();

        // comprobar si es administrador global y, en ese caso, devolver todas las organizaciones
        if ($user->isGlobalAdministrator()) {
            $organizationsCount = $em->getRepository(Organization::class)
                ->countOrganizationsByPerson($user);

            if ($organizationsCount > 1) {
                $this->session->set(
                    '_security.organization.target_path',
                    $this->session->get('_security.main.target_path')
                );
            } else {
                $organization = $em->getRepository(Organization::class)->findFirstByUserOrNull($user);
                $this->session->set('organization_id', $organization->getId());
            }

            return;
        }

        // no es administrador global, consultar las pertenencias activas
        $organizationsCount = $em->getRepository(Organization::class)
            ->countOrganizationsByPerson($user);

        switch ($organizationsCount) {
            case 0:
                throw new CustomUserMessageAuthenticationException('form.login.error.no_membership');
            case 1:
                $organization = $em->getRepository(Organization::class)->findFirstByUserOrNull($user);
                $this->session->set('organization_id', $organization->getId());
                break;
            default:
                $this->session->set(
                    '_security.organization.target_path',
                    $this->session->get('_security.main.target_path')
                );
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin'];
    }
}
