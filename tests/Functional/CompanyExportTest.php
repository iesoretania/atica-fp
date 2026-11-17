<?php

namespace App\Tests\Functional;

use App\Entity\Company;
use App\Entity\Workcenter;
use Doctrine\ORM\EntityManagerInterface;
use OpenSpout\Reader\XLSX\Reader;

/**
 * Exportación de empresas a Excel en el formato de importación de nexo-fp.
 */
class CompanyExportTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }

    private function addCompany(string $name, string $code): void
    {
        // los setters de Company no son fluidos
        $company = new Company();
        $company->setName($name);
        $company->setCode($code);
        $company->setCity('Linares');
        $workcenter = (new Workcenter())->setCompany($company)->setName('Sede de ' . $name)->setCity('Linares');
        $this->em()->persist($company);
        $this->em()->persist($workcenter);
        $this->em()->flush();
    }

    /**
     * @return array<string, list<list<string>>> filas (con la cabecera) de cada hoja
     */
    private function readWorkbook(): array
    {
        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('spreadsheetml', (string) $response->headers->get('Content-Type'));

        // el fichero temporal se borra tras enviarlo: el contenido está en la respuesta interna del cliente
        $path = tempnam(sys_get_temp_dir(), 'atica_test_') . '.xlsx';
        file_put_contents($path, $this->client->getInternalResponse()->getContent());
        $reader = new Reader();
        $reader->open($path);
        $sheets = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $sheets[$sheet->getName()][] = array_map('strval', $row->toArray());
            }
        }
        $reader->close();
        unlink($path);

        return $sheets;
    }

    public function testOptionsScreenIsShownBeforeExporting(): void
    {
        $this->login($this->getAdmin());
        $this->client->request('GET', '/empresa/exportar');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="company_export[workplaceTraining]"]');
        self::assertSelectorExists('input[name="company_export[workLinkedLearning]"]');
        self::assertSelectorExists('input[name="company_export[inCompanyTraining]"]');
        self::assertSelectorExists('select[name="company_export[academicYear]"]');
    }

    public function testExportUsesTheNexoFormat(): void
    {
        $this->addCompany('IES Colaborador', '23004455');
        $this->addCompany('Empresa corta', 'B123');

        $this->login($this->getAdmin());
        $this->client->request('GET', '/empresa/exportar', ['company_export' => ['academicYear' => '']]);
        $sheets = $this->readWorkbook();

        self::assertSame(
            ['Nombre *', 'CIF/NIF *', 'Código de centro', 'Localidad *'],
            array_slice($sheets['Empresas'][0], 0, 4)
        );
        self::assertSame(['CIF/NIF de la empresa *', 'Código de centro de la empresa', 'Nombre del centro *', 'Localidad'], $sheets['Centros de trabajo'][0]);
        self::assertArrayHasKey('Empleados', $sheets);

        $byName = [];
        foreach (array_slice($sheets['Empresas'], 1) as $row) {
            $byName[$row[0]] = $row;
        }

        // un CIF de 8 dígitos es un centro educativo: CIF S4111001F y el código en su columna
        self::assertSame('S4111001F', $byName['IES Colaborador'][1]);
        self::assertSame('23004455', $byName['IES Colaborador'][2]);
        self::assertContains(['S4111001F', '23004455', 'Sede de IES Colaborador', 'Linares'], $sheets['Centros de trabajo']);

        // una empresa normal conserva su CIF
        self::assertSame('B23000001', $byName['Tecnologías del Sur S.L.'][1]);
        self::assertSame('', $byName['Tecnologías del Sur S.L.'][2]);

        // nexo-fp rechazaría el CIF de menos de 9 caracteres: se omite y se avisa en una hoja aparte
        self::assertArrayNotHasKey('Empresa corta', $byName);
        self::assertArrayHasKey('Avisos', $sheets);
        self::assertStringContainsString('Empresa corta', implode(' ', array_merge(...$sheets['Avisos'])));
    }

    public function testWorkTutorsAreIncludedOnlyWhenAsked(): void
    {
        $this->login($this->getAdmin());

        $this->client->request('GET', '/empresa/exportar', ['company_export' => ['academicYear' => '']]);
        $without = $this->readWorkbook();
        self::assertCount(1, $without['Empleados'], 'Sin marcar nada solo hay cabecera');

        $this->client->request('GET', '/empresa/exportar', ['company_export' => [
            'workplaceTraining' => '1', 'workLinkedLearning' => '1', 'inCompanyTraining' => '1', 'academicYear' => '',
        ]]);
        $with = $this->readWorkbook();

        // los tutores de las fixtures no tienen DNI/NIE: nexo-fp los rechazaría, así que se omiten y se avisa
        self::assertCount(1, $with['Empleados']);
        self::assertStringContainsString('Tutor laboral', implode(' ', array_merge(...$with['Avisos'])), 'Se avisa de los tutores omitidos');
    }

    public function testWorkTutorsWithIdentifierAreExportedAsEmployees(): void
    {
        $tutor = $this->getUser('alfa.tutorlaboral');
        $tutor->setUniqueIdentifier('12345678Z');
        $this->em()->flush();

        $this->login($this->getAdmin());
        $this->client->request('GET', '/empresa/exportar', ['company_export' => [
            'workplaceTraining' => '1', 'workLinkedLearning' => '1', 'inCompanyTraining' => '1', 'academicYear' => '',
        ]]);
        $sheets = $this->readWorkbook();

        $employees = array_slice($sheets['Empleados'], 1);
        self::assertNotEmpty($employees, 'El tutor laboral de los acuerdos se incluye como empleado');
        foreach ($employees as $row) {
            self::assertNotSame('', $row[0], 'Cada empleado indica el CIF de su empresa');
            self::assertSame('12345678Z', $row[4]);
            self::assertSame('alfa.tutorlaboral@example.com', $row[5]);
        }
        // sin repeticiones: una fila por tutor y empresa aunque aparezca en varios acuerdos o módulos
        self::assertSame(count($employees), count(array_unique(array_map(static fn (array $r): string => $r[0] . $r[1] . $r[4], $employees))));
    }

    public function testOnlyCompanyManagersCanExport(): void
    {
        $this->login($this->getRegularUser());
        $this->client->request('GET', '/empresa/exportar');

        self::assertResponseStatusCodeSame(403);
    }
}
