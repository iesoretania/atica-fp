<?php

namespace App\Tests\Functional;

use App\Entity\Edu\ReportTemplate;
use App\Tests\Fixtures\DemoData;
use App\Tests\Fixtures\EduFixtures;

/**
 * Plantillas de informe (PDF guardado en la base de datos): listado y descarga
 */
class ReportTemplateTest extends AppWebTestCase
{
    private function templateOf(string $organizationCode): ReportTemplate
    {
        $template = static::getContainer()->get('doctrine')->getRepository(ReportTemplate::class)
            ->findOneBy(['organization' => $this->getOrganization($organizationCode)]);
        self::assertInstanceOf(ReportTemplate::class, $template);

        return $template;
    }

    private function downloadUrl(ReportTemplate $template): string
    {
        return static::getContainer()->get('router')->generate('organization_report_template_download', ['id' => $template->getId()]);
    }

    public function testLocalAdministratorDownloadsTheStoredPdf(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $this->client->request('GET', $this->downloadUrl($this->templateOf(DemoData::ORG_A_CODE)));

        self::assertResponseIsSuccessful();
        self::assertSame('application/pdf', $this->client->getResponse()->headers->get('Content-Type'));
        // es una respuesta en streaming: el contenido está en la respuesta interna del cliente
        self::assertSame(EduFixtures::MINIMAL_PDF, $this->client->getInternalResponse()->getContent());
    }

    public function testTemplateIsListedForTheLocalAdministrator(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $this->client->request('GET', static::getContainer()->get('router')->generate('organization_report_template_list'));

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Plantilla de informe de prueba', $this->client->getResponse()->getContent());
    }

    public function testOtherOrganizationAdministratorCannotDownloadIt(): void
    {
        $template = $this->templateOf(DemoData::ORG_A_CODE);

        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->client->request('GET', $this->downloadUrl($template));

        self::assertResponseStatusCodeSame(403);
    }

    public function testAnonymousUsersCannotDownloadIt(): void
    {
        $this->client->request('GET', $this->downloadUrl($this->templateOf(DemoData::ORG_A_CODE)));

        self::assertResponseRedirects();
    }
}
