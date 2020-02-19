<?php
/*
  Copyright (C) 2018-2020: Luis Ramón López López

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

namespace AppBundle\Service;

use AppBundle\Entity\User;
use Symfony\Component\Translation\TranslatorInterface;

class MailerService
{
    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    private $prefix;
    private $from;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct($prefix, $from, \Swift_Mailer $mailer, TranslatorInterface $translator)
    {
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->prefix = $prefix;
        $this->from = $from;
    }

    /**
     * @param User[] $users
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
            $to[$user->getEmailAddress()] = (string) $user;
        }

        /**
         * @var \Swift_Message
         */
        $msg = $this->mailer->createMessage()
            ->setSubject($this->prefix . $this->translator->
                trans($subject['id'], $subject['parameters'], $translationDomain))
            ->setFrom($this->from)
            ->setTo($to)
            ->setBody($this->translator->trans($body['id'], $body['parameters'], $translationDomain));

        return $this->mailer->send($msg);
    }
}
