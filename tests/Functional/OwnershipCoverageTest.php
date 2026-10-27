<?php

namespace App\Tests\Functional;

use App\Entity\Company;
use App\Entity\EventLog;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\Workcenter;
use App\Listener\OrganizationOwnershipSubscriber;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Toda entidad con datos debe poder asociarse a una organización para que
 * OrganizationOwnershipSubscriber pueda proteger las URL que la reciben como argumento.
 * Si se añade una entidad nueva sin la cadena de pertenencia (por ejemplo, un getter nuevo hacia
 * su "padre"), este test falla y obliga a ampliar PARENT_GETTERS o a justificar que es global.
 */
class OwnershipCoverageTest extends AppWebTestCase
{
    /**
     * Entidades que no pertenecen a ninguna organización. EventLog es el registro de accesos del sistema: solo
     * tiene datos si alguien ha entrado en la aplicación fuera de una transacción de test.
     */
    private const GLOBAL_ENTITIES = [Person::class, Company::class, Workcenter::class, EventLog::class];

    public function testEveryEntityWithDataResolvesToAnOrganization(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $subscriber = new OrganizationOwnershipSubscriber($this->createStub(AuthorizationCheckerInterface::class));
        $finder = new \ReflectionMethod($subscriber, 'findOrganization');

        $unresolved = [];
        $checked = 0;
        foreach ($em->getMetadataFactory()->getAllMetadata() as $metadata) {
            if ($metadata->isMappedSuperclass || $metadata->isEmbeddedClass) {
                continue;
            }
            $entities = $em->getRepository($metadata->getName())->findBy([], null, 5);
            if ($entities === []) {
                continue;
            }
            $checked++;
            $resolved = false;
            foreach ($entities as $entity) {
                $resolved = $resolved || $finder->invoke($subscriber, $entity) instanceof Organization;
            }
            if (!$resolved) {
                $unresolved[] = $metadata->getName();
            }
            $em->clear();
        }

        self::assertGreaterThan(40, $checked, 'Se han comprobado muy pocas entidades');
        self::assertSame(
            [],
            array_values(array_diff($unresolved, self::GLOBAL_ENTITIES)),
            'Entidades que no se pueden asociar a una organización'
        );
        // las entidades que sí son globales siguen sin poder asociarse (si dejaran de serlo, este test debe actualizarse)
        self::assertContains(Person::class, $unresolved);
    }
}
