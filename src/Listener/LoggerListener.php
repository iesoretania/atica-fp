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

use App\Entity\EventLog;
use App\Entity\Person;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoggerListener implements EventSubscriberInterface
{
    private $managerRegistry;

    private $token;

    private $authenticationUtils;

    private $requestStack;

    public function __construct(
        ManagerRegistry $managerRegistry,
        TokenStorageInterface $token,
        AuthenticationUtils $authenticationUtils,
        RequestStack $requestStack
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->token = $token;
        $this->authenticationUtils = $authenticationUtils;
        $this->requestStack = $requestStack;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if ($event->isMainRequest()) {
            /** @var Person $user */
            $user = $this->token->getToken() !== null ? $this->token->getToken()->getUser() : null;
            $user = is_string($user) ? null : $user;

            $ip = $event->getRequest()->getClientIp();
            $eventName = EventLog::ACCESS;
            $data = $event->getRequest()->getPathInfo();

            $this->createLogEntry($eventName, $user, $ip, $data);
        }
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        /** @var Person $user */
        $user = $event->getAuthenticationToken()->getUser();

        $ip = $event->getRequest()->getClientIp();
        $eventName = EventLog::LOGIN_SUCCESS;
        $data = $user->getLoginUsername();

        $this->createLogEntry($eventName, $user, $ip, $data);
    }

    public function onSecuritySwitchUser(SwitchUserEvent $event)
    {
        /** @var Person $user */
        $user = $event->getTargetUser();

        $ip = $event->getRequest()->getClientIp();
        $eventName = EventLog::SWITCH_USER;
        $data = $event->getTargetUser()->getLoginUsername();

        $this->createLogEntry($eventName, $user, $ip, $data);
    }


    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        /** @var Person $user */
        $user = $event->getAuthenticationToken()->getUser();

        $ip = $this->requestStack->getMasterRequest()->getClientIp();
        $eventName = EventLog::LOGIN_ERROR;
        $data = $this->authenticationUtils->getLastUsername();

        $this->createLogEntry($eventName, $user, $ip, $data);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
            SecurityEvents::SWITCH_USER => 'onSecuritySwitchUser',
            AuthenticationEvents::AUTHENTICATION_FAILURE => 'onAuthenticationFailure'
        ];
    }

    /**
     * @param $eventName
     * @param Person $user
     * @param $ip
     * @param $data
     * @throws \Exception
     */
    private function createLogEntry($eventName, Person $user = null, $ip = null, $data = null)
    {
        $em = $this->managerRegistry->getManager();
        $logEntry = new EventLog();
        $logEntry
            ->setDateTime(new \DateTime())
            ->setEvent($eventName)
            ->setIp($ip)
            ->setData($data)
            ->setUser($user);
        $em->persist($logEntry);
        $em->flush();
    }
}
