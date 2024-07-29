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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class RequestListener implements EventSubscriberInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TokenStorageInterface
     */
    private $token;

    /**
     * @var AccessDecisionManagerInterface
     */
    private $accessDecisionManager;

    public function __construct(RouterInterface $router, TokenStorageInterface $token, AccessDecisionManagerInterface $accessDecisionManager)
    {
        $this->router = $router;
        $this->token = $token;
        $this->accessDecisionManager = $accessDecisionManager;
    }

    final public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->getRequest()->hasSession()) {
            return;
        }

        $session = $event->getRequest()->getSession();

        if (($session->get('organization_id', '') === '') && $event->isMainRequest()) {
            $route = $event->getRequest()->get('_route');
            if ($route && !str_starts_with($route, 'log') && $route[0] !== '_') {
                $session->set('_security.organization.target_path', $event->getRequest()->getUri());
                $event->setResponse(
                    new RedirectResponse($this->router->generate('login_organization'))
                );
            }
        }

        if (
            $event->isMainRequest() &&
            $this->token->getToken() &&
            $this->token->getToken()->getUser() instanceof Person &&
            $this->token->getToken()->getUser()->isForcePasswordChange() &&
            !$this->token->getToken()->getUser()->getExternalCheck() &&
            !$this->accessDecisionManager->decide($this->token->getToken(), ['IS_IMPERSONATOR'])
        ) {
            $route = $event->getRequest()->get('_route');
            if ($route && !str_starts_with($route, 'log') && !str_starts_with($route, 'force') && $route[0] !== '_') {
                $session->set('_security.force_password_change.target_path', $event->getRequest()->getUri());
                $event->setResponse(
                    new RedirectResponse($this->router->generate('force_password_reset_do'))
                );
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest'
        ];
    }
}
