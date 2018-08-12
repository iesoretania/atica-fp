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

namespace AppBundle\Controller;

use AppBundle\Entity\Organization;
use AppBundle\Entity\User;
use AppBundle\Repository\OrganizationRepository;
use AppBundle\Service\MailerService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SecurityController extends Controller
{
    /**
     * @Route("/entrar", name="login", methods={"GET"})
     */
    public function loginAction()
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        // obtener el error de entrada, si existe alguno
        $error = $authenticationUtils->getLastAuthenticationError();

        // último nombre de usuario introducido
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            'security/login.html.twig',
            array(
                'last_username' => $lastUsername,
                'login_error' => $error,
            )
        );
    }

    /**
     * @Route("/comprobar", name="login_check", methods={"POST"})
     * @Route("/salir", name="logout", methods={"GET"})
     */
    public function logInOutCheckAction()
    {
    }

    /**
     * @Route("/restablecer", name="login_password_reset", methods={"GET", "POST"})
     */
    public function passwordResetRequestAction(Request $request)
    {

        $data = [
            'email' => ''
        ];

        $form = $this->createForm('AppBundle\Form\Type\PasswordResetType', $data);

        $form->handleRequest($request);

        $data = $form->getData();
        $email = $data['email'];
        $error = '';

        // ¿se ha enviado una dirección?
        if ($form->isSubmitted() && $form->isValid()) {
            $error = $this->passwordResetRequest($email);

            if (!is_string($error)) {
                return $error;
            }
        }

        return $this->render(
            'security/login_password_reset.html.twig', [
                'last_username' => $this->get('session')->get('_security.last_username', ''),
                'form' => $form->createView(),
                'error' => $error
            ]
        );
    }

    /**
     * @Route("/restablecer/correo/{userId}/{token}", name="email_reset_do", methods={"GET", "POST"})
     */
    public function emailResetAction(Request $request, $userId, $token)
    {
        /**
         * @var User
         */
        $user = $this->getDoctrine()->getManager()->getRepository('AppBundle:User')->findOneBy([
            'id' => $userId,
            'token' => $token
        ]);

        if (null === $user || $user->getTokenType() === 'password' || $user->getTokenExpiration() < new \DateTime()) {
            $this->addFlash('error', $this->get('translator')->trans('form.change_email.notvalid', [], 'security'));
            return $this->redirectToRoute('login');
        }

        if ($request->getMethod() === 'POST') {
            $user
                ->setEmailAddress($user->getTokenType())
                ->setToken(null)
                ->setTokenExpiration(null)
                ->setTokenType(null);

            try {
                $this->getDoctrine()->getManager()->flush();

                // indicar que los cambios se han realizado con éxito y volver a la página de inicio
                $this->addFlash('success', $this->get('translator')->trans('form.change_email.message', [], 'security'));
            } catch (\Exception $e) {
                // indicar que no se ha podido cambiar
                $this->addFlash('error', $this->get('translator')->trans('form.change_email.error', [], 'security'));
            }
            return new RedirectResponse(
                $this->generateUrl('frontpage')
            );
        }

        return $this->render(
            'security/login_email_change.html.twig', [
                'user' => $user
            ]
        );
    }

    /**
     * @Route("/restablecer/{userId}/{token}", name="login_password_reset_do", methods={"GET", "POST"})
     */
    public function passwordResetAction(Request $request, $userId, $token)
    {
        /**
         * @var User
         */
        $user = $this->getDoctrine()->getManager()->getRepository('AppBundle:User')->findOneBy([
            'id' => $userId,
            'token' => $token,
            'tokenType' => 'password'
        ]);

        if (null === $user || ($user->getTokenExpiration() < new \DateTime())) {
            $this->addFlash('error', $this->get('translator')->trans('form.reset.notvalid', [], 'security'));
            return $this->redirectToRoute('login');
        }

        $data = [
            'password' => '',
            'repeat' => ''
        ];

        $form = $this->createForm('AppBundle\Form\Type\NewPasswordType', $data);

        $form->handleRequest($request);

        $error = '';
        if ($form->isSubmitted() && $form->isValid()) {

            //codificar la nueva contraseña y asignarla al usuario
            $password = $this->get('security.password_encoder')
                ->encodePassword($user, $form->get('newPassword')->get('first')->getData());

            $user
                ->setPassword($password)
                ->setToken(null)
                ->setTokenExpiration(null)
                ->setTokenType(null);

            $this->getDoctrine()->getManager()->flush();

            // indicar que los cambios se han realizado con éxito y volver a la página de inicio
            $message = $this->get('translator')->trans('form.reset.message', [], 'security');
            $this->addFlash('success', $message);
            return new RedirectResponse(
                $this->generateUrl('frontpage')
            );
        }

        return $this->render(
            'security/login_password_new.html.twig', [
                'user' => $user,
                'form' => $form->createView(),
                'error' => $error
            ]
        );
    }

    /**
     * @Route("/organizacion", name="login_organization", methods={"GET", "POST"})
     */
    public function organizationAction(Request $request)
    {
        // si no hay usuario activo, volver
        if (null === $this->getUser()) {
            return $this->redirectToRoute('login');
        }

        /** @var Session $session */
        $session = $this->get('session');

        $data = ['organization' => $this->getUser()->getDefaultOrganization()];

        $count = $this->getDoctrine()->getManager()->getRepository('AppBundle:Organization')->countOrganizationsByUser($this->getUser(), new \DateTime());

        $form = $this->createFormBuilder($data)
            ->add('organization', EntityType::class, [
                'expanded' => $count < 5,
                'class' => Organization::class,
                'query_builder' => function(OrganizationRepository $er) {
                    return $er->getMembershipByUserQueryBuilder($this->getUser(), new \DateTime());
                },
                'required' => true
            ])
            ->getForm();

        $form->handleRequest($request);

        // ¿se ha seleccionado una organización?
        if ($form->isSubmitted() && $form->isValid() && $form->get('organization')->getData()) {

            $session->set('organization_id', $form->get('organization')->getData()->getId());
            $session->set('organization_selected', true);
            $this->getUser()->setDefaultOrganization($form->get('organization')->getData());
            $this->getDoctrine()->getManager()->flush();

            $url = $session->get('_security.organization.target_path', $this->generateUrl('frontpage'));
            $session->remove('_security.organization.target_path');
            return new RedirectResponse($url);
        }
        return $this->render('security/login_organization.html.twig', [
                'form' => $form->createView(),
                'count' => $count
            ]
        );
    }

    /**
     * @param $email
     * @return string|RedirectResponse
     */
    private function passwordResetRequest($email)
    {
        /** @var User $user */
        // comprobar que está asociada a un usuario
        $user = $this->getDoctrine()->getManager()->getRepository('AppBundle:User')->findOneBy(['emailAddress' => $email]);

        $error = '';

        if (null === $user) {
            $error = $this->get('translator')->trans('form.reset.notfound', [], 'security');
        } else {
            // almacenar como último correo electrónico el indicado
            $this->get('session')->set('_security.last_username', $user->getEmailAddress());

            // obtener tiempo de expiración del token
            $expire = (int) $this->getParameter('password_reset.expire');

            if ($this->getParameter('external.enabled') && $user->getExternalCheck()) {
                $this->addFlash('error', $this->get('translator')->trans('form.reset.external_login.error', [], 'security'));
            } else {
                // comprobar que no se ha generado un token hace poco
                if ($user->getToken() && $user->getTokenExpiration() > new \DateTime()) {
                    $error = $this->get('translator')->trans('form.reset.wait', ['%expiry%' => $expire], 'security');
                } else {
                    // generar un nuevo token
                    $token = bin2hex(random_bytes(16));
                    $user->setToken($token);

                    // calcular fecha de expiración del token
                    $validity = new \DateTime();
                    $validity->add(new \DateInterval('PT'.$expire.'M'));
                    $user->setTokenExpiration($validity)->setTokenType('password');

                    // enviar correo
                    if (0 === $this->get(MailerService::class)->sendEmail([$user],
                            ['id' => 'form.reset.email.subject', 'parameters' => []],
                            [
                                'id' => 'form.reset.email.body',
                                'parameters' => [
                                    '%name%' => $user->getPerson()->getFirstName(),
                                    '%link%' => $this->generateUrl('login_password_reset_do',
                                        ['userId' => $user->getId(), 'token' => $token],
                                        UrlGeneratorInterface::ABSOLUTE_URL),
                                    '%expiry%' => $expire
                                ]
                            ], 'security')
                    ) {
                        $this->addFlash('error', $this->get('translator')->trans('form.reset.error', [], 'security'));
                    } else {
                        // guardar token
                        $this->get('doctrine')->getManager()->flush();

                        $this->addFlash('success',
                            $this->get('translator')->trans('form.reset.sent', ['%email%' => $email], 'security'));
                        return $this->redirectToRoute('login');
                    }
                }
            }
        }
        return $error;
    }

}
