<?php

namespace App\Tests\Fixtures;

use App\Entity\Company;
use App\Entity\Workcenter;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Empresas y centros de trabajo. Las empresas no pertenecen a ninguna organización, por lo que
 * no se usa el nombre de las organizaciones en sus datos.
 */
class CompanyFixtures extends AbstractDemoFixture implements DependentFixtureInterface
{
    public const REF_COMPANY_SOUTH = 'company.south';
    public const REF_COMPANY_NORTH = 'company.north';
    public const REF_WORKCENTER_MAIN = 'workcenter.main';
    public const REF_WORKCENTER_BRANCH = 'workcenter.branch';
    public const REF_WORKCENTER_UBEDA = 'workcenter.ubeda';
    public const REF_WORKCENTER_BAEZA = 'workcenter.baeza';

    public function getDependencies(): array
    {
        return [CoreFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $companies = [
            ['Tecnologías del Sur S.L.', 'B23000001', 'Linares', '23700', self::REF_COMPANY_SOUTH, [
                [self::REF_WORKCENTER_MAIN, 'Sede central', 'Linares'],
                [self::REF_WORKCENTER_BRANCH, 'Delegación de Jaén', 'Jaén'],
            ]],
            ['Consultoría del Norte S.A.', 'A23000002', 'Úbeda', '23400', self::REF_COMPANY_NORTH, [
                [self::REF_WORKCENTER_UBEDA, 'Oficina de Úbeda', 'Úbeda'],
            ]],
            ['Desarrollos de la Sierra S.L.', 'B23000003', 'Baeza', '23440', null, [
                [self::REF_WORKCENTER_BAEZA, 'Taller de Baeza', 'Baeza'],
            ]],
        ];

        foreach ($companies as [$name, $code, $city, $zip, $companyReference, $workcenters]) {
            $company = new Company();
            // los setters de Company no son fluidos
            $company->setName($name);
            $company->setCode($code);
            $company->setAddress('Calle Mayor, 1');
            $company->setCity($city);
            $company->setZipCode($zip);
            $company->setPhoneNumber('953000000');
            $company->setEmailAddress(strtolower($code) . '@example.com');
            $manager->persist($company);
            if ($companyReference !== null) {
                $this->addReference($companyReference, $company);
            }

            foreach ($workcenters as [$reference, $workcenterName, $workcenterCity]) {
                $workcenter = new Workcenter();
                $workcenter
                    ->setCompany($company)
                    ->setName($workcenterName)
                    ->setAddress('Avenida de la Industria, 2')
                    ->setCity($workcenterCity)
                    ->setZipCode($zip)
                    ->setPhoneNumber('953111111')
                    ->setEmailAddress(strtolower($code) . '.' . $reference . '@example.com');
                $manager->persist($workcenter);
                $this->addReference($reference, $workcenter);
            }
        }
        $manager->flush();
    }
}
