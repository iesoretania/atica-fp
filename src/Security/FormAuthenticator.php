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

namespace App\Security;

use App\Entity\Person;
use App\Repository\PersonRepository;
use App\Service\SenecaAuthenticatorService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class FormAuthenticator extends AbstractAuthenticator implements InteractiveAuthenticatorInterface
{
    /**
     * Constructor
     */
    public function __construct(private readonly RouterInterface $router, private readonly UserPasswordHasherInterface $encoder, private readonly SenecaAuthenticatorService $senecaAuthenticator, private readonly PersonRepository $personRepository, private readonly ManagerRegistry $managerRegistry)
    {
    }

    final public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'login_check' && $request->isMethod('POST');
    }

    /**
     * {@inheritdoc}
     */
    final public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetPath = $request->getSession()->get('_security.' . $firewallName . '.target_path');
        if (!$targetPath) {
            $targetPath = $this->router->generate('frontpage');
        }
        return new RedirectResponse($targetPath);
    }

    /**
     * {@inheritdoc}
     */
    final public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        $url = $this->router->generate('login');
        return new RedirectResponse($url);
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $url = $this->router->generate('login');
        return new RedirectResponse($url);
    }

    /**
     * @inheritDoc
     */
    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('_username');
        $plainPassword = $request->request->get('_password');

        $user = $this->personRepository->findOneByUniqueIdentifierOrUsernameOrEmailAddress($username);

        if (!$user instanceof Person) {
            throw new UserNotFoundException();
        }

        // ¿Comprobación de contraseña desde Séneca?
        if ($user->getExternalCheck()) {
            $result = $this->senecaAuthenticator->checkUserCredentials($user->getLoginUsername(), $plainPassword);

            if ($result) {
                // contraseña correcta, actualizar en local por si perdemos la conectividad
                if (!$this->encoder->isPasswordValid($user, $plainPassword) || $this->encoder->needsRehash($user)) {
                    $user->setPassword($this->encoder->hashPassword($user, $plainPassword));
                    $em = $this->managerRegistry->getManager();
                    $em->flush();
                }
                return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier()));
            }
            // intentar en local
        }

        // comprobación local
        if (!$this->encoder->isPasswordValid($user, $plainPassword)) {
            throw new BadCredentialsException();
        }

        if ($this->encoder->needsRehash($user)) {
            $user->setPassword($this->encoder->hashPassword($user, $plainPassword));
            $em = $this->managerRegistry->getManager();
            $em->flush();
        }

        return new Passport(
            new UserBadge($username, $this->personRepository->findOneByUniqueIdentifierOrUsernameOrEmailAddress(...)),
            new PasswordCredentials($plainPassword)
        );
    }

    /**
     * @inheritDoc
     */
    final public function isInteractive(): bool
    {
        return true;
    }
}
