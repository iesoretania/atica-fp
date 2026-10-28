<?php

namespace App\Tests\Functional;

use App\Entity\Edu\ReportTemplate;
use App\Tests\Fixtures\DemoData;
use App\Tests\Fixtures\EduFixtures;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Administración de las plantillas de informe: alta, edición con subida de PDF, validación del fichero y borrado.
 */
class ReportTemplateAdminTest extends AppWebTestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function tempFile(string $content, string $name = 'plantilla.pdf'): string
    {
        $path = sys_get_temp_dir() . '/' . uniqid('tpl_', true) . '_' . $name;
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return $path;
    }

    private function templateCount(): int
    {
        return $this->em()->getRepository(ReportTemplate::class)->count([]);
    }

    private function templateOf(string $code): ReportTemplate
    {
        return $this->em()->getRepository(ReportTemplate::class)->findOneBy(['organization' => $this->getOrganization($code)]);
    }

    /**
     * Rellena y envía el formulario de plantilla con el fichero indicado
     */
    private function submitTemplate(string $url, string $description, ?string $file): void
    {
        $crawler = $this->client->request('GET', $url);
        $form = $crawler->selectButton('Guardar cambios')->form(['report_template[description]' => $description]);
        if ($file !== null) {
            $form['report_template[newData]']->upload($file);
        }
        $this->client->submit($form);
    }

    public function testAdministratorUploadsANewTemplateAndDownloadsTheSameFile(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->templateCount();

        $this->submitTemplate('/centro/plantilla/nueva', 'Informe de seguimiento', $this->tempFile(EduFixtures::MINIMAL_PDF));

        self::assertResponseRedirects('/centro/plantilla/listar');
        self::assertSame($before + 1, $this->templateCount());
        $template = $this->em()->getRepository(ReportTemplate::class)->findOneBy(['description' => 'Informe de seguimiento']);
        self::assertSame($this->getOrganization()->getId(), $template->getOrganization()->getId());

        $this->client->request('GET', '/centro/plantilla/descarga/' . $template->getId());
        self::assertSame(EduFixtures::MINIMAL_PDF, $this->client->getInternalResponse()->getContent());
    }

    public function testFileThatIsNotAPdfIsRejected(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->templateCount();

        // un texto o un script con extensión .pdf no es un PDF
        $this->submitTemplate('/centro/plantilla/nueva', 'Falso PDF', $this->tempFile("<?php echo 'hola';", 'falso.pdf'));

        self::assertResponseStatusCodeSame(200);
        self::assertSame($before, $this->templateCount());
    }

    public function testHtmlFileIsRejected(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->templateCount();

        $this->submitTemplate('/centro/plantilla/nueva', 'Página web', $this->tempFile('<html><script>alert(1)</script></html>', 'pagina.html'));

        self::assertSame($before, $this->templateCount());
    }

    public function testEmptyFileIsRejected(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->templateCount();

        $this->submitTemplate('/centro/plantilla/nueva', 'Vacía', $this->tempFile(''));

        self::assertSame($before, $this->templateCount());
    }

    public function testTemplateWithoutFileOrDescriptionIsRejected(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->templateCount();

        $this->submitTemplate('/centro/plantilla/nueva', 'Sin fichero', null);
        self::assertSame($before, $this->templateCount());

        $this->submitTemplate('/centro/plantilla/nueva', '', $this->tempFile(EduFixtures::MINIMAL_PDF));
        self::assertSame($before, $this->templateCount());
    }

    public function testAdministratorReplacesTheStoredFile(): void
    {
        $template = $this->templateOf(DemoData::ORG_A_CODE);
        $newPdf = EduFixtures::MINIMAL_PDF . "\n% revisado";
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->submitTemplate('/centro/plantilla/' . $template->getId(), 'Descripción nueva', $this->tempFile($newPdf));

        self::assertResponseRedirects('/centro/plantilla/listar');
        self::assertSame('Descripción nueva', $this->em()->getRepository(ReportTemplate::class)->find($template->getId())->getDescription());
        $this->client->request('GET', '/centro/plantilla/descarga/' . $template->getId());
        self::assertSame($newPdf, $this->client->getInternalResponse()->getContent());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonManagerProvider(): iterable
    {
        foreach (['jefe', 'tutor', 'profesor', 'tutorlaboral', 'alumno1', 'coordinador', 'gestorffe', 'economico'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider nonManagerProvider
     */
    public function testNonManagersCannotCreateChangeOrDeleteTemplates(string $username): void
    {
        $template = $this->templateOf(DemoData::ORG_A_CODE);
        $original = ['description' => $template->getDescription()];
        $before = $this->templateCount();
        $this->login($this->getUser($username));

        // aunque puedan ver la plantilla, un envío manipulado no la modifica ni crea otra
        $this->client->request('POST', '/centro/plantilla/nueva', ['report_template' => ['description' => 'Nueva ajena']]);
        $this->client->request('POST', '/centro/plantilla/' . $template->getId(), ['report_template' => ['description' => 'Cambiada']]);
        $this->client->request('POST', '/centro/plantilla/eliminar', ['items' => [$template->getId()], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/centro/plantilla/listar');
        self::assertResponseStatusCodeSame(403);

        self::assertSame($before, $this->templateCount());
        self::assertSame($original['description'], $this->templateOf(DemoData::ORG_A_CODE)->getDescription());
    }

    public function testTemplateOfAnotherOrganizationCannotBeEditedOrDeleted(): void
    {
        $foreign = $this->templateOf(DemoData::ORG_B_CODE);
        $original = $foreign->getDescription();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', '/centro/plantilla/' . $foreign->getId());
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/centro/plantilla/' . $foreign->getId(), ['report_template' => ['description' => 'Cambiada']]);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/centro/plantilla/eliminar', ['items' => [$foreign->getId()], 'confirm' => 'ok']);

        self::assertSame($original, $this->templateOf(DemoData::ORG_B_CODE)->getDescription());
    }

    public function testDeletionRequiresConfirmation(): void
    {
        $template = $this->templateOf(DemoData::ORG_A_CODE);
        $id = $template->getId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', '/centro/plantilla/eliminar', ['items' => [$id]]);
        self::assertResponseIsSuccessful();
        self::assertNotNull($this->em()->getRepository(ReportTemplate::class)->find($id));

        $this->client->request('POST', '/centro/plantilla/eliminar', ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseRedirects('/centro/plantilla/listar');
        self::assertNull($this->em()->getRepository(ReportTemplate::class)->find($id));
    }
}
