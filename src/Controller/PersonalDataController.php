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

namespace App\Controller;

use App\Entity\Person;
use App\Form\Type\PersonType;
use App\Service\MailerService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PersonalDataController extends AbstractController
{
    #[Route(path: '/datos', name: 'personal_data', methods: ['GET', 'POST'])]
    public function userProfileForm(
        Request $request,
        TranslatorInterface $translator,
        UserPasswordHasherInterface $passwordEncoder,
        ManagerRegistry $managerRegistry,
        MailerService $mailerService
    ): Response {
        /** @var Person $user */
        $user = $this->getUser();

        $form = $this->createForm(PersonType::class, $user, [
            'own' => true,
            'admin' => $user->isGlobalAdministrator()
        ]);

        $oldEmail = $user->getEmailAddress();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $passwordSubmitted = $this->processPasswordAndEmailChanges(
                $form,
                $user,
                $oldEmail,
                $mailerService,
                $translator,
                $passwordEncoder
            );

            $message = $translator->trans(
                $passwordSubmitted ? 'message.password_changed' : 'message.saved',
                [],
                'user'
            );

            try {
                $managerRegistry->getManager()->flush();
                $this->addFlash('success', $message);
                return $this->redirectToRoute('frontpage');
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'user'));
            }
        }

        return $this->render('user/personal_data_form.html.twig', [
            'menu_path' => 'frontpage',
            'breadcrumb' => [
                ['caption' => 'menu.personal_data']
            ],
            'title' => $translator->trans('user.data', [], 'layout'),
            'form' => $form->createView(),
            'user' => $user,
            'last_url' => $this->generateUrl('frontpage')
        ]);
    }

    /**
     * Requests an email address change confirmation
     *
     * @param string $oldEmail
     * @throws \Exception
     */
    private function requestEmailAddressChange(
        Person $user,
        ?string $oldEmail,
        MailerService $mailerService,
        TranslatorInterface $translator
    ): void {
        $newEmail = $user->getEmailAddress();

        if ($newEmail === '') {
            $newEmail = null;
        }

        if (null === $newEmail || $user->isGlobalAdministrator()) {
            $user->setEmailAddress($newEmail);
        } else {
            $user->setTokenType($newEmail);
            // generar un nuevo token
            $token = bin2hex(random_bytes(16));
            $user->setToken($token);

            // obtener tiempo de expiración del token
            $expire = (int) $this->getParameter('password_reset.expire');

            // calcular fecha de expiración del token
            $validity = new \DateTime();
            $validity->add(new \DateInterval('PT' . $expire . 'M'));
            $user->setTokenExpiration($validity);

            $user->setEmailAddress($newEmail);

            // enviar correo
            if (0 === $mailerService->sendEmail(
                [$user],
                ['id' => 'form.change_email.email.subject', 'parameters' => []],
                [
                    'id' => 'form.change_email.email.body',
                    'parameters' => [
                        '%name%' => $user->getFirstName(),
                        '%link%' => $this->generateUrl(
                            'email_reset_do',
                            ['userId' => $user->getId(), 'token' => $token],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                        '%expiry%' => $expire
                    ]
                ],
                'security'
            )) {
                $this->addFlash('error', $translator->trans('message.email_change.error', [], 'user'));
            } else {
                $this->addFlash(
                    'info',
                    $translator->trans('message.email_change.info', ['%email%' => $newEmail], 'user')
                );
            }

            $user->setEmailAddress($oldEmail);
        }
    }

    /**
     * Checks if a password/email change has been requested and process it
     * @param string $oldEmail
     * @return bool
     * @throws \Exception
     */
    private function processPasswordAndEmailChanges(
        FormInterface $form,
        Person $user,
        $oldEmail,
        MailerService $mailerService,
        TranslatorInterface $translator,
        UserPasswordHasherInterface $passwordEncoder
    ) {
        // comprobar si ha cambiado el correo electrónico
        if ($user->getEmailAddress() !== $oldEmail) {
            $this->requestEmailAddressChange($user, $oldEmail, $mailerService, $translator);
        }

        // Si es solicitado, cambiar la contraseña
        $passwordSubmitted = ($form->has('changePassword') &&
                $form->get('changePassword') instanceof SubmitButton) && $form->get('changePassword')->isClicked();
        if ($passwordSubmitted) {
            $user->setPassword($passwordEncoder
                ->hashPassword($user, $form->get('newPassword')->get('first')->getData()));
        }
        return $passwordSubmitted;
    }
}
