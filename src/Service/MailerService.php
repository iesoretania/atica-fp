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

namespace App\Service;

use App\Entity\Person;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

class MailerService
{
    private MailerInterface $mailer;

    private $prefix;
    private $from;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct($prefix, $from, MailerInterface $mailer, TranslatorInterface $translator)
    {
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->prefix = $prefix;
        $this->from = $from;
    }

    /**
     * @param Person[] $users
     * @param array $subject
     * @param array $body
     * @param string|null $translationDomain
     *
     * @return int
     */
    public function sendEmail($users, $subject, $body, $translationDomain = null)
    {
        // convertir array de usuarios en lista de correos
        $to = [];
        foreach ($users as $user) {
            $to[$user->getEmailAddress()] = $user->__toString();
        }

        /** @var Email $msg */
        $msg = (new Email())
            ->subject($this->prefix . $this->translator->
                trans($subject['id'], $subject['parameters'], $translationDomain))
            ->from($this->from)
            ->text($this->translator->trans($body['id'], $body['parameters'], $translationDomain));

        foreach ($to as $email => $name) {
            $msg->addTo(new Address($email, $name));
        }

        try {
            $this->mailer->send($msg);
        } catch (TransportExceptionInterface $e) {
            return 0;
        }

        return count($to);
    }
}
