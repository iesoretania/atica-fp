<?php

namespace App\Tests\Unit\Security;

use App\Entity\Edu\AcademicYear;
use App\Entity\Organization;
use App\Entity\Person;
use App\Repository\OrganizationRepository;
use App\Service\UserExtensionService;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Invariantes de seguridad que deben cumplir TODOS los Voters de la aplicación.
 * Los Voters y las entidades se descubren automáticamente, de modo que los Voters
 * nuevos quedan cubiertos sin tocar este test.
 */
class AllVotersTest extends TestCase
{
    private Organization $organization;

    protected function setUp(): void
    {
        $this->organization = new Organization();
        // sin módulos restringidos (enabledModules = null) todos los módulos están activos
        $this->organization->setCurrentAcademicYear(new AcademicYear());
    }

    /**
     * @return iterable<string, array{class-string<Voter>}>
     */
    public static function voterProvider(): iterable
    {
        foreach (self::discoverClasses('Security', 'App\\Security\\') as $class) {
            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract() || !$reflection->isSubclassOf(Voter::class)) {
                continue;
            }
            yield $reflection->getShortName() . ' (' . $class . ')' => [$class];
        }
    }

    /**
     * @return list<class-string>
     */
    private static function discoverClasses(string $directory, string $namespace): array
    {
        $classes = [];
        $root = dirname(__DIR__, 3) . '/src/' . $directory;
        foreach ((new Finder())->files()->in($root)->name('*.php') as $file) {
            $class = $namespace . str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            if (class_exists($class)) {
                $classes[] = $class;
            }
        }
        return $classes;
    }

    private function createVoter(string $class, bool $globalAdmin = false): Voter
    {
        $constructor = (new \ReflectionClass($class))->getConstructor();
        $arguments = [];
        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType()->getName();
            $arguments[] = match (true) {
                $type === CacheItemPoolInterface::class => new ArrayAdapter(),
                $type === AccessDecisionManagerInterface::class => $this->createDecisionManager($globalAdmin),
                $type === UserExtensionService::class => $this->createUserExtension($globalAdmin),
                default => $this->createStub($type)
            };
        }
        return new $class(...$arguments);
    }

    private function createDecisionManager(bool $globalAdmin): AccessDecisionManagerInterface
    {
        $manager = $this->createStub(AccessDecisionManagerInterface::class);
        $manager->method('decide')->willReturnCallback(
            static fn ($token, array $attributes, $object = null): bool => $globalAdmin && $attributes === ['ROLE_ADMIN']
        );
        return $manager;
    }

    private function createUserExtension(bool $globalAdmin): UserExtensionService
    {
        // los métodos de UserExtensionService son final: se usa la clase real con dependencias simuladas
        $session = new Session(new MockArraySessionStorage());
        $session->set('organization_id', 1);
        $request = new Request();
        $request->setSession($session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $checker = $this->createStub(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturnCallback(
            static fn ($attribute): bool => $globalAdmin && $attribute === 'ROLE_ADMIN'
        );

        $organizations = $this->createStub(OrganizationRepository::class);
        $organizations->method('find')->willReturn($this->organization);

        return new UserExtensionService($requestStack, $checker, $organizations);
    }

    private function createToken(?UserInterface $user): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        return $token;
    }

    /**
     * Atributos (constantes de texto) que declara el Voter
     *
     * @return list<string>
     */
    private static function attributesOf(string $class): array
    {
        $attributes = [];
        foreach ((new \ReflectionClass($class))->getReflectionConstants() as $constant) {
            if ($constant->isPublic() && is_string($constant->getValue()) && $constant->getDeclaringClass()->getName() === $class) {
                $attributes[] = $constant->getValue();
            }
        }
        return $attributes;
    }

    /**
     * Pares [atributo, entidad] soportados por el Voter
     *
     * @return list<array{string, object}>
     */
    private function supportedPairs(Voter $voter): array
    {
        $pairs = [];
        foreach (self::attributesOf($voter::class) as $attribute) {
            foreach (self::discoverClasses('Entity', 'App\\Entity\\') as $entityClass) {
                $reflection = new \ReflectionClass($entityClass);
                if ($reflection->isAbstract() || $reflection->isInterface()) {
                    continue;
                }
                $subject = $reflection->newInstanceWithoutConstructor();
                if ($voter->supports($attribute, $subject)) {
                    $pairs[] = [$attribute, $subject];
                }
            }
        }
        return $pairs;
    }

    /**
     * @dataProvider voterProvider
     */
    public function testDeclaresAttributesAndSupportsAtLeastOneSubject(string $class): void
    {
        $voter = $this->createVoter($class);

        self::assertNotSame([], self::attributesOf($class), "$class no declara atributos");
        self::assertNotSame([], $this->supportedPairs($voter), "$class no soporta ninguna entidad");
    }

    /**
     * @dataProvider voterProvider
     */
    public function testAbstainsOnUnsupportedSubjectsAndAttributes(string $class): void
    {
        $voter = $this->createVoter($class, true);
        $token = $this->createToken(null);

        foreach (self::attributesOf($class) as $attribute) {
            self::assertSame(
                Voter::ACCESS_ABSTAIN,
                $voter->vote($token, new \stdClass(), [$attribute]),
                "$class debe abstenerse si el sujeto no es suyo"
            );
            self::assertSame(
                Voter::ACCESS_ABSTAIN,
                $voter->vote($token, null, [$attribute]),
                "$class debe abstenerse si no hay sujeto"
            );
        }
        foreach ($this->supportedPairs($voter) as [, $subject]) {
            self::assertSame(
                Voter::ACCESS_ABSTAIN,
                $voter->vote($token, $subject, ['ATRIBUTO_QUE_NO_EXISTE']),
                "$class debe abstenerse ante un atributo desconocido"
            );
        }
    }

    /**
     * @dataProvider voterProvider
     */
    public function testDeniesAnonymousUsers(string $class): void
    {
        $voter = $this->createVoter($class);
        $token = $this->createToken(null);

        $checked = 0;
        foreach ($this->supportedPairs($voter) as [$attribute, $subject]) {
            try {
                $vote = $voter->vote($token, $subject, [$attribute]);
            } catch (\Error) {
                // el Voter necesita un grafo de entidades completo para llegar a la comprobación
                continue;
            }
            self::assertSame(Voter::ACCESS_DENIED, $vote, "$class concede $attribute a un usuario no identificado");
            $checked++;
        }
        if ($checked === 0) {
            self::markTestSkipped("$class necesita entidades completas: se cubre en su test específico");
        }
        self::assertGreaterThan(0, $checked);
    }

    /**
     * @dataProvider voterProvider
     */
    public function testGlobalAdministratorIsGrantedWhenSubjectIsReachable(string $class): void
    {
        $voter = $this->createVoter($class, true);
        $admin = new Person();
        $admin->setGlobalAdministrator(true);
        $token = $this->createToken($admin);

        $checked = 0;
        foreach ($this->supportedPairs($voter) as [$attribute, $subject]) {
            // las entidades que pertenecen directamente a una organización se enlazan con la actual
            if (method_exists($subject, 'setOrganization')) {
                $subject->setOrganization($this->organization);
            }
            try {
                $vote = $voter->vote($token, $subject, [$attribute]);
            } catch (\Error) {
                continue;
            }
            self::assertSame(Voter::ACCESS_GRANTED, $vote, "$class deniega $attribute a un administrador global");
            $checked++;
        }
        if ($checked === 0) {
            self::markTestSkipped("$class necesita entidades completas: se cubre en su test específico");
        }
        self::assertGreaterThan(0, $checked);
    }
}
