<?php

namespace App\Tests\Functional;

use App\Entity\ItpModule\StudentProgramWorkcenterActivityComment;
use App\Entity\WltModule\AgreementActivityRealizationComment;
use App\Listener\OrganizationOwnershipSubscriber;
use App\Tests\Fixtures\DemoData;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * El borrado de comentarios de valoración recibe el comentario en la URL: un usuario de otra
 * organización no debe poder verlo ni borrarlo, aunque sea administrador local de la suya.
 */
class CrossOrganizationCommentTest extends AppWebTestCase
{
    /**
     * @return iterable<string, array{string, class-string}>
     */
    public static function commentProvider(): iterable
    {
        yield 'FFE' => ['in_company_training_phase_tracking_grading_comment_delete', StudentProgramWorkcenterActivityComment::class];
        yield 'FP dual' => ['work_linked_training_evaluation_comment_delete', AgreementActivityRealizationComment::class];
    }

    private function commentOf(string $class, string $organizationCode): object
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $organizationId = $this->getOrganization($organizationCode)->getId();
        $subscriber = new OrganizationOwnershipSubscriber($this->createStub(AuthorizationCheckerInterface::class));
        $findOrganization = new \ReflectionMethod($subscriber, 'findOrganization');

        foreach ($em->getRepository($class)->findAll() as $comment) {
            if ($findOrganization->invoke($subscriber, $comment)?->getId() === $organizationId) {
                return $comment;
            }
        }
        self::fail("No hay comentarios de $class en la organización $organizationCode");
    }

    /**
     * @dataProvider commentProvider
     */
    public function testOtherOrganizationAdministratorCannotDeleteTheComment(string $route, string $class): void
    {
        $comment = $this->commentOf($class, DemoData::ORG_A_CODE);
        $id = $comment->getId();
        $url = static::getContainer()->get('router')->generate($route, $this->parameters($route, $id));

        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        foreach (['GET', 'POST'] as $method) {
            $this->client->request($method, $url, $method === 'POST' ? ['confirm' => 'ok'] : []);
            self::assertResponseStatusCodeSame(403, "$method $route");
        }

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();
        self::assertNotNull($em->find($class, $id), 'El comentario de la otra organización ha sido borrado');
    }

    /**
     * Control positivo: el administrador de la propia organización sí puede acceder
     *
     * @dataProvider commentProvider
     */
    public function testOwnOrganizationAdministratorCanReachTheComment(string $route, string $class): void
    {
        $comment = $this->commentOf($class, DemoData::ORG_A_CODE);
        $url = static::getContainer()->get('router')->generate($route, $this->parameters($route, $comment->getId()));

        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $this->client->request('GET', $url);

        self::assertNotSame(403, $this->client->getResponse()->getStatusCode());
        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return array<string, int>
     */
    private function parameters(string $route, int $id): array
    {
        return $route === 'in_company_training_phase_tracking_grading_comment_delete'
            ? ['activityComment' => $id]
            : ['id' => $id];
    }
}
