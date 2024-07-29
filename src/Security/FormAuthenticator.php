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

namespace App\Security;

use App\Entity\Person;
use App\Repository\PersonRepository;
use App\Service\SenecaAuthenticatorService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class FormAuthenticator extends AbstractAuthenticator implements InteractiveAuthenticatorInterface
{
    /**
     * @var UserPasswordHasherInterface
     */
    private $encoder;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var SenecaAuthenticatorService
     */
    private $senecaAuthenticator;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;
    private PersonRepository $personRepository;

    /**
     * Constructor
     */
    public function __construct(
        RouterInterface $router,
        UserPasswordHasherInterface $encoder,
        SenecaAuthenticatorService $senecaAuthenticator,
        PersonRepository $personRepository,
        ManagerRegistry $managerRegistry
    ) {
        $this->router = $router;
        $this->encoder = $encoder;
        $this->senecaAuthenticator = $senecaAuthenticator;
        $this->personRepository = $personRepository;
        $this->managerRegistry = $managerRegistry;
    }

    final public function supports(Request $request): bool
    {
        $session = $request->getSession();

        return !(
            $request->attributes->get('_route') !== 'login_check' || !$request->isMethod('POST') || !$session
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request): mixed
    {
        $username = $request->request->get('_username');
        $session = $request->getSession();

        $session->set(Security::LAST_USERNAME, $username);

        return array(
            'username' => $username,
            'password' => $request->request->get('_password'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        try {
            return $userProvider->loadUserByUsername($credentials['username']);
        } catch (UsernameNotFoundException $e) {
            throw new BadCredentialsException();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        if (!$user instanceof Person) {
            throw new AuthenticationServiceException();
        }

        $plainPassword = $credentials['password'];

        // ¿Comprobación de contraseña desde Séneca?
        if ($user->getExternalCheck()) {
            $result = $this->senecaAuthenticator->checkUserCredentials($user->getLoginUsername(), $plainPassword);

            if ($result) {
                // contraseña correcta, actualizar en local por si perdemos la conectividad
                if (!$this->encoder->isPasswordValid($user, $plainPassword)) {
                    $user->setPassword($this->encoder->hashPassword($user, $plainPassword));
                    $em = $this->managerRegistry->getManager();
                    $em->flush();
                }
                return true;
            }
            // intentar en local
        }

        // comprobación local
        if (!$this->encoder->isPasswordValid($user, $plainPassword)) {
            throw new BadCredentialsException();
        }

        return true;
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
     * {@inheritdoc}
     */
    public function supportsRememberMe(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(Request $request)
    {
        $username = $request->request->get('_username');
        $plainPassword = $request->request->get('_password');

        $user = $this->personRepository->findOneByUniqueIdentifierOrUsernameOrEmailAddress($username);

        if ($user === null) {
            throw new UserNotFoundException();
        }

        if (!$user instanceof Person) {
            throw new AuthenticationServiceException();
        }

        // ¿Comprobación de contraseña desde Séneca?
        if ($user->getExternalCheck()) {
            $result = $this->senecaAuthenticator->checkUserCredentials($user->getLoginUsername(), $plainPassword);

            if ($result) {
                // contraseña correcta, actualizar en local por si perdemos la conectividad
                if (!$this->encoder->isPasswordValid($user, $plainPassword)) {
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

        return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier()));
    }

    /**
     * @inheritDoc
     */
    public function isInteractive(): bool
    {
        return true;
    }
}
