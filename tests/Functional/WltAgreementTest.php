<?php

namespace App\Tests\Functional;

use App\Entity\WltModule\Agreement;
use App\Entity\WltModule\Project;
use App\Entity\WltModule\WorkDay;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Acuerdos de FP dual: listado, formularios, borrado y copia del calendario.
 *  - acuerdo 1: alumno5; acuerdo 2: alumno6
 */
class WltAgreementTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function projectId(string $code = DemoData::ORG_A_CODE): int
    {
        return $this->em()->getRepository(Project::class)->findOneBy(['organization' => $this->getOrganization($code)])->getId();
    }

    /**
     * @return list<int>
     */
    private function agreementIds(int $projectId): array
    {
        return array_map(
            static fn(Agreement $a): int => $a->getId(),
            $this->em()->getRepository(Agreement::class)->findBy(['project' => $projectId], ['id' => 'ASC'])
        );
    }

    private function workDayCount(int $agreementId): int
    {
        return $this->em()->getRepository(WorkDay::class)->count(['agreement' => $agreementId]);
    }

    /**
     * @return list<string>
     */
    private function readUrls(int $projectId, int $agreementId): array
    {
        return ["/dual/acuerdo/$projectId/listar", "/dual/acuerdo/nuevo/$projectId", "/dual/acuerdo/$agreementId"];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function managerProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin'];
        yield 'coordinador' => ['alfa.coordinador'];
        yield 'jefe de departamento' => ['alfa.jefe'];
    }

    /**
     * @dataProvider managerProvider
     */
    public function testManagersOpenTheScreens(string $username): void
    {
        $project = $this->projectId();
        $this->login($this->getUser($username));

        foreach ($this->readUrls($project, $this->agreementIds($project)[0]) as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($url);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        foreach (['tutor', 'profesor', 'tutorlaboral', 'alumno5', 'alumno1', 'gestorffe', 'economico'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotSeeOrChangeAgreements(string $username): void
    {
        $project = $this->projectId();
        $agreements = $this->agreementIds($project);
        $this->login($this->getUser($username));

        foreach ($this->readUrls($project, $agreements[0]) as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', "/dual/acuerdo/operacion/$project", ['items' => $agreements, 'delete' => '', 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame($agreements, $this->agreementIds($project));
    }

    public function testAdministratorOfAnotherOrganizationCannotUseThem(): void
    {
        $project = $this->projectId();
        $agreements = $this->agreementIds($project);
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach ($this->readUrls($project, $agreements[0]) as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', "/dual/acuerdo/operacion/$project", ['items' => $agreements, 'delete' => '', 'confirm' => 'ok']);

        self::assertSame($agreements, $this->agreementIds($project));
    }

    /**
     * @dataProvider managerProvider
     */
    public function testNewAgreementFormIsEditableForEveryManager(string $username): void
    {
        $project = $this->projectId();
        $this->login($this->getUser($username));

        $crawler = $this->client->request('GET', "/dual/acuerdo/nuevo/$project");

        // un acuerdo todavía sin alumno no está cerrado: el formulario se puede rellenar y guardar
        self::assertResponseIsSuccessful();
        self::assertSame(0, $crawler->filter('form [disabled]')->count());
        self::assertSame(1, $crawler->selectButton('Guardar cambios')->count());
    }

    public function testNewAgreementFormIsReadOnlyWhenTheProjectIsLocked(): void
    {
        $project = $this->projectId();
        $em = $this->em();
        $em->find(Project::class, $project)->setLocked(true);
        $em->flush();
        $this->login($this->getUser('alfa.coordinador'));

        $crawler = $this->client->request('GET', "/dual/acuerdo/nuevo/$project");

        self::assertSame(0, $crawler->selectButton('Guardar cambios')->count());
    }

    public function testManagerEditsTheDefaultSchedule(): void
    {
        $project = $this->projectId();
        $agreement = $this->agreementIds($project)[0];
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', "/dual/acuerdo/$agreement");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'agreement[defaultStartTime1]' => '09:00',
            'agreement[defaultEndTime1]' => '15:00',
        ]));

        self::assertResponseRedirects("/dual/acuerdo/$project/listar");
        $saved = $this->em()->getRepository(Agreement::class)->find($agreement);
        self::assertSame('09:00', $saved->getDefaultStartTime1());
        self::assertSame('15:00', $saved->getDefaultEndTime1());
    }

    public function testAgreementsAreDeletedOnlyWithConfirmation(): void
    {
        $project = $this->projectId();
        $agreements = $this->agreementIds($project);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/dual/acuerdo/operacion/$project", ['items' => [$agreements[1]], 'delete' => '']);
        self::assertResponseIsSuccessful();
        self::assertSame($agreements, $this->agreementIds($project));

        $this->client->request('POST', "/dual/acuerdo/operacion/$project", ['items' => [$agreements[1]], 'delete' => '', 'confirm' => 'ok']);
        self::assertResponseRedirects("/dual/acuerdo/$project/listar");
        self::assertSame([$agreements[0]], $this->agreementIds($project));
    }

    public function testDeletingOrCopyingUnknownAgreementsDoesNotFail(): void
    {
        $project = $this->projectId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        foreach (['delete', 'copy'] as $operation) {
            $this->client->request('POST', "/dual/acuerdo/operacion/$project", ['items' => [999999], $operation => '']);
            self::assertLessThan(500, $this->client->getResponse()->getStatusCode(), $operation);
        }
    }

    public function testAgreementsOfAnotherProjectCannotBeDeletedThroughThisOne(): void
    {
        $own = $this->projectId();
        $foreign = $this->projectId(DemoData::ORG_B_CODE);
        $foreignAgreements = $this->agreementIds($foreign);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/dual/acuerdo/operacion/$own", ['items' => $foreignAgreements, 'delete' => '', 'confirm' => 'ok']);

        self::assertSame($foreignAgreements, $this->agreementIds($foreign));
    }

    public function testCalendarCanBeCopiedFromAnotherAgreement(): void
    {
        $project = $this->projectId();
        [$first, $second] = $this->agreementIds($project);
        $sourceDays = $this->workDayCount($second);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('POST', "/dual/acuerdo/operacion/$project", ['items' => [$first], 'copy' => '']);
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Realizar copia')->form([
            'calendar_copy[agreement]' => (string) $second,
        ]));

        self::assertResponseRedirects("/dual/acuerdo/$project/listar");
        self::assertSame($sourceDays, $this->workDayCount($first));
        self::assertSame($sourceDays, $this->workDayCount($second));
    }
}
