<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Company;
use App\Entity\Edu\AcademicYear;
use App\Entity\Organization;
use App\Entity\Workcenter;
use Doctrine\ORM\EntityManagerInterface;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Exporta las empresas registradas en el libro Excel que importa nexo-fp (hojas «Empresas», «Centros de trabajo» y
 * «Empleados»; los rótulos son los de nexo-fp y no se pueden traducir ni cambiar).
 *
 * Reglas:
 *  - Si el CIF son 8 dígitos es un centro educativo: se exporta con el CIF S4111001F y el código de centro en su columna.
 *  - nexo-fp rechaza el libro entero si una fila no cumple sus reglas, así que se omiten (y se avisa) las empresas con un
 *    CIF de menos de 9 caracteres o repetido y los tutores sin DNI/NIE válido.
 *  - Opcionalmente, los tutores laborales que figuran en los acuerdos de FCT y FP dual y en los convenios de FFEOE
 *    se exportan como empleados de la empresa de su centro de trabajo.
 *
 * @phpstan-type Export array{companies: list<list<string>>, workcenters: list<list<string>>, workers: list<list<string>>, warnings: list<string>}
 */
final class CompanyExcelExportService
{
    public const EDUCATIONAL_CENTRE_VAT = 'S4111001F';

    private const CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    private const HEADER_COLOR = 'E8EDF7';

    /** Hoja => [rótulo, ancho]; los obligatorios para nexo-fp llevan además « *» en la cabecera. */
    private const SHEETS = [
        'Empresas' => [
            ['Nombre', 34, true],
            ['CIF/NIF', 16, true],
            ['Código de centro', 16, false],
            ['Localidad', 22, true],
            ['Nombre del representante', 22, false],
            ['Apellidos del representante', 26, false],
            ['DNI/NIE del representante', 18, false],
            ['Cargo del representante', 24, false],
            ['Información de contacto', 44, false],
            ['Observaciones', 44, false],
        ],
        'Centros de trabajo' => [
            ['CIF/NIF de la empresa', 18, true],
            ['Código de centro de la empresa', 18, false],
            ['Nombre del centro', 34, true],
            ['Localidad', 22, false],
        ],
        'Empleados' => [
            ['CIF/NIF de la empresa', 18, true],
            ['Código de centro de la empresa', 18, false],
            ['Nombre', 22, true],
            ['Apellidos', 28, true],
            ['DNI/NIE', 16, true],
            ['Correo electrónico', 32, false],
            ['Teléfono', 18, false],
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Empresas que no se pueden exportar (sin tener en cuenta los tutores), para avisar antes de exportar.
     *
     * @return list<string>
     */
    public function omittedCompanies(): array
    {
        return $this->build(false, false, false, null, null)['warnings'];
    }

    /**
     * @return Export
     */
    public function build(bool $workplaceTraining, bool $workLinkedLearning, bool $inCompanyTraining, ?AcademicYear $academicYear, ?Organization $organization): array
    {
        $warnings = [];

        $workcentersByCompany = [];
        foreach ($this->em->createQueryBuilder()
            ->select('w')->from(Workcenter::class, 'w')
            ->orderBy('w.name')->addOrderBy('w.city')
            ->getQuery()->getResult() as $workcenter) {
            $workcentersByCompany[$workcenter->getCompany()->getId()][] = $workcenter;
        }

        $companies = $workcenters = [];
        /** @var array<int, array{vat: string, centreCode: string}> $exported */
        $exported = [];
        $keys = [];

        /** @var list<Company> $list */
        $list = $this->em->createQueryBuilder()
            ->select('c', 'm')->from(Company::class, 'c')->leftJoin('c.manager', 'm')
            ->orderBy('c.name')->addOrderBy('c.city')
            ->getQuery()->getResult();

        foreach ($list as $company) {
            $code = trim((string) $company->getCode());
            $isCentre = preg_match('/^\d{8}$/', $code) === 1;
            $vat = $isCentre ? self::EDUCATIONAL_CENTRE_VAT : $code;
            $centreCode = $isCentre ? $code : '';

            if (mb_strlen($vat) < 9 || mb_strlen($vat) > 50) {
                $warnings[] = $this->translator->trans('export.warning.invalid_vat', ['name' => $company->getName(), 'code' => $code], 'company');
                continue;
            }

            // nexo-fp compara los CIF sin espacios, guiones ni puntos y sin distinguir mayúsculas
            $key = mb_strtoupper((string) preg_replace('/[\s\-.]+/', '', $vat)) . ($centreCode !== '' ? '#' . $centreCode : '');
            if (isset($keys[$key])) {
                $warnings[] = $this->translator->trans('export.warning.duplicate_vat', ['name' => $company->getName(), 'code' => $code], 'company');
                continue;
            }
            $keys[$key] = true;
            $exported[$company->getId()] = ['vat' => $vat, 'centreCode' => $centreCode];

            $manager = $company->getManager();
            $companies[] = [
                mb_substr((string) $company->getName(), 0, 255),
                $vat,
                $centreCode,
                mb_substr((string) $company->getCity(), 0, 255),
                $manager?->getFirstName() ?? '',
                $manager?->getLastName() ?? '',
                $manager?->getUniqueIdentifier() ?? '',
                '',
                $this->contactInformation($company),
                '',
            ];

            foreach ($workcentersByCompany[$company->getId()] ?? [] as $workcenter) {
                $workcenters[] = [$vat, $centreCode, mb_substr((string) $workcenter->getName(), 0, 255), mb_substr((string) $workcenter->getCity(), 0, 255)];
            }
        }

        $workers = [];
        if ($workplaceTraining || $workLinkedLearning || $inCompanyTraining) {
            $workers = $this->workers($exported, $workplaceTraining, $workLinkedLearning, $inCompanyTraining, $academicYear, $organization, $warnings);
        }

        return ['companies' => $companies, 'workcenters' => $workcenters, 'workers' => $workers, 'warnings' => $warnings];
    }

    /**
     * @param array<int, array{vat: string, centreCode: string}> $exported empresas exportadas por id
     * @param list<string>                                        $warnings
     * @return list<list<string>>
     */
    private function workers(array $exported, bool $wpt, bool $wlt, bool $itp, ?AcademicYear $academicYear, ?Organization $organization, array &$warnings): array
    {
        // Cada origen: alias de la tabla de la que cuelga el tutor y los JOIN hasta el centro de trabajo y la matrícula.
        // %s es el campo del tutor (principal o adicional).
        $sources = [];
        if ($wpt) {
            $sources[] = ['App\Entity\WptModule\AgreementEnrollment ae JOIN ae.agreement ag JOIN ag.workcenter w JOIN ae.%s p JOIN ae.studentEnrollment se'];
        }
        if ($wlt) {
            $sources[] = ['App\Entity\WltModule\Agreement ag JOIN ag.workcenter w JOIN ag.%s p JOIN ag.studentEnrollment se'];
        }
        if ($itp) {
            $sources[] = ['App\Entity\ItpModule\StudentProgramWorkcenter spw JOIN spw.workcenter w JOIN spw.%s p JOIN spw.studentProgram sp JOIN sp.studentEnrollment se'];
        }

        /** @var array<string, array{companyId: int|string, id: int, firstName: ?string, lastName: ?string, identifier: ?string, email: ?string}> $persons */
        $persons = [];
        foreach ($sources as [$from]) {
            foreach (['workTutor', 'additionalWorkTutor'] as $field) {
                $dql = 'SELECT DISTINCT IDENTITY(w.company) AS companyId, p.id AS id, p.firstName AS firstName, p.lastName AS lastName,'
                    . ' p.uniqueIdentifier AS identifier, p.emailAddress AS email FROM ' . sprintf($from, $field)
                    . ' JOIN se.group g JOIN g.grade gr JOIN gr.training t JOIN t.academicYear ay';
                $query = $academicYear instanceof AcademicYear
                    ? $this->em->createQuery($dql . ' WHERE ay = :year')->setParameter('year', $academicYear)
                    : $this->em->createQuery($dql . ' WHERE ay.organization = :organization')->setParameter('organization', $organization);

                foreach ($query->getResult() as $row) {
                    $persons[$row['companyId'] . '|' . $row['id']] = $row;
                }
            }
        }

        $workers = [];
        $seen = [];
        $omitted = [];
        foreach ($persons as $person) {
            $companyId = (int) $person['companyId'];
            if (!isset($exported[$companyId])) {
                continue;
            }
            $id = trim((string) $person['identifier']);
            $firstName = trim((string) $person['firstName']);
            $lastName = trim((string) $person['lastName']);
            if (mb_strlen($id) < 9 || mb_strlen($id) > 20 || $firstName === '' || $lastName === '') {
                $omitted[$person['id']] = $person;
                continue;
            }
            $dupKey = $companyId . '|' . mb_strtoupper($id);
            if (isset($seen[$dupKey])) {
                continue;
            }
            $seen[$dupKey] = true;

            $email = trim((string) $person['email']);
            $workers[] = [
                $exported[$companyId]['vat'],
                $exported[$companyId]['centreCode'],
                $firstName,
                $lastName,
                $id,
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : '',
                '',
            ];
        }

        foreach ($omitted as $person) {
            $warnings[] = $this->translator->trans('export.warning.invalid_worker', [
                'name' => trim($person['lastName'] . ', ' . $person['firstName'], ' ,'),
                'id' => (string) $person['identifier'] !== '' ? (string) $person['identifier'] : '—',
            ], 'company');
        }

        return $workers;
    }

    private function contactInformation(Company $company): string
    {
        $lines = [];
        $address = trim(implode(', ', array_filter([
            trim((string) $company->getAddress()),
            trim(implode(' ', array_filter([trim((string) $company->getZipCode()), trim((string) $company->getCity())]))),
        ])));
        foreach ([
            'export.contact.address' => $address,
            'export.contact.phone' => trim((string) $company->getPhoneNumber()),
            'export.contact.fax' => trim((string) $company->getFaxNumber()),
            'export.contact.email' => trim((string) $company->getEmailAddress()),
            'export.contact.web' => trim((string) $company->getWebSite()),
        ] as $label => $value) {
            if ($value !== '') {
                $lines[] = $this->translator->trans($label, [], 'company') . ': ' . $value;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param Export $export
     */
    public function createResponse(array $export, string $filename): BinaryFileResponse
    {
        $tempPath = sys_get_temp_dir() . '/' . uniqid('atica_companies_', true) . '.xlsx';

        $writer = new Writer();
        $writer->openToFile($tempPath);

        $rowsBySheet = [
            'Empresas' => $export['companies'],
            'Centros de trabajo' => $export['workcenters'],
            'Empleados' => $export['workers'],
        ];

        $first = true;
        foreach (self::SHEETS as $name => $columns) {
            $sheet = $first ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
            $first = false;
            $sheet->setName($name);
            $sheet->setSheetView((new SheetView())->setFreezeRow(2));

            $headers = [];
            $index = 1;
            foreach ($columns as [$label, $width, $required]) {
                $headers[] = $required ? $label . ' *' : $label;
                $sheet->setColumnWidth($width, $index++);
            }
            $writer->addRow($this->textRow($headers, (new Style())->setFontBold()->setBackgroundColor(self::HEADER_COLOR)));
            foreach ($rowsBySheet[$name] as $row) {
                $writer->addRow($this->textRow($row));
            }
        }

        // nexo-fp ignora las hojas que no conoce: esta recoge lo que se ha dejado fuera
        if ($export['warnings'] !== []) {
            $sheet = $writer->addNewSheetAndMakeItCurrent();
            $sheet->setName($this->translator->trans('export.warning.sheet', [], 'company'));
            $sheet->setColumnWidth(120, 1);
            $writer->addRow($this->textRow([$this->translator->trans('export.warning.title', [], 'company')], (new Style())->setFontBold()));
            foreach ($export['warnings'] as $warning) {
                $writer->addRow($this->textRow([$warning]));
            }
        }

        $writer->close();

        $response = new BinaryFileResponse($tempPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->headers->set('Content-Type', self::CONTENT_TYPE);
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * Todas las celdas como texto: así no se interpretan como fórmula ni se convierten en número CIF, DNI o códigos.
     *
     * @param list<string> $values
     */
    private function textRow(array $values, ?Style $style = null): Row
    {
        return new Row(array_map(static fn (string $value): StringCell => new StringCell($value, $style), $values));
    }
}
