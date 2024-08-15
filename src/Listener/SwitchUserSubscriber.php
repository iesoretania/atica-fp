<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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

use App\Service\UserExtensionService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class SwitchUserSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly UserExtensionService $userExtension, private readonly RequestStack $requestStack)
    {
    }
    final public function onSwitchUser(SwitchUserEvent $event): void
    {
        if (false === $this->userExtension->checkCurrentOrganization($event->getTargetUser())) {
            $this->requestStack->getSession()->remove('organization_id');
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::SWITCH_USER => 'onSwitchUser',
        ];
    }
}
