<?php

namespace App\Tests\Functional;

use App\Entity\Edu\ContactMethod;
use App\Entity\Edu\Grade;
use App\Entity\Edu\Group;
use App\Entity\Edu\NonWorkingDay;
use App\Entity\Edu\PerformanceScale;
use App\Entity\Edu\PerformanceScaleValue;
use App\Entity\Edu\Subject;
use App\Entity\Edu\Training;
use App\Entity\Edu\TravelRoute;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Form;

/**
 * Formularios de configuración del centro (días no lectivos, métodos de contacto, itinerarios, escalas y sus valores,
 * enseñanzas, niveles, grupos y materias): alta, validación, edición, borrado con confirmación, permisos y
 * aislamiento entre organizaciones. Cada entidad se describe con las direcciones de sus pantallas y los datos
 * mínimos de un alta válida.
 */
class ConfigurationCrudTest extends AppWebTestCase
{
    private const NAME = 'Zz prueba';

    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function year(string $code = DemoData::ORG_A_CODE): int
    {
        return $this->getOrganization($code)->getCurrentAcademicYear()->getId();
    }

    private function scale(string $code = DemoData::ORG_A_CODE): int
    {
        return (int) $this->em()->getConnection()->fetchOne(
            'SELECT id FROM edu_performance_scale WHERE organization_id = ? ORDER BY id LIMIT 1',
            [$this->getOrganization($code)->getId()]
        );
    }

    /**
     * Descripción de cada entidad: clase, campo con el nombre, direcciones y campos del formulario.
     *
     * @return array<string, array{class: class-string, field: string, form: string, new: string, edit: string, delete: string, data: array<string, string>, users: list<string>}>
     */
    private function entities(): array
    {
        $year = $this->year();
        $scale = $this->scale();
        $department = (string) $this->em()->getConnection()->fetchOne(
            'SELECT d.id FROM edu_department d WHERE d.academic_year_id = ? ORDER BY d.id LIMIT 1',
            [$year]
        );

        return [
            'día no lectivo' => [
                'class' => NonWorkingDay::class, 'field' => 'description', 'form' => 'non_working_day',
                'new' => "/centro/dia_no_lectivo/nuevo/$year", 'edit' => '/centro/dia_no_lectivo/{id}',
                'delete' => "/centro/dia_no_lectivo/eliminar/$year", 'data' => ['date' => '2027-03-20'],
                'users' => ['alfa.admin'],
            ],
            'método de contacto' => [
                'class' => ContactMethod::class, 'field' => 'description', 'form' => 'contact_method',
                'new' => '/centro/contactos/nuevo', 'edit' => '/centro/contactos/{id}',
                'delete' => "/centro/contactos/eliminar/$year", 'data' => [], 'users' => ['alfa.admin'],
            ],
            'itinerario' => [
                'class' => TravelRoute::class, 'field' => 'description', 'form' => 'travel_route',
                'new' => '/centro/itinerario/nuevo', 'edit' => '/centro/itinerario/detalle/{id}',
                'delete' => '/centro/itinerario/eliminar', 'data' => ['distance' => '12'],
                'users' => ['alfa.admin', 'alfa.economico'],
            ],
            'escala de calificación' => [
                'class' => PerformanceScale::class, 'field' => 'description', 'form' => 'performance_scale',
                'new' => '/centro/escala/nueva', 'edit' => '/centro/escala/{id}',
                'delete' => '/centro/escala/eliminar', 'data' => [], 'users' => ['alfa.admin'],
            ],
            'valor de escala' => [
                'class' => PerformanceScaleValue::class, 'field' => 'description', 'form' => 'performance_scale_value',
                'new' => "/centro/escala/valor/nueva/$scale", 'edit' => '/centro/escala/valor/{id}',
                'delete' => "/centro/escala/valor/$scale/eliminar", 'data' => ['numericGrade' => '7'],
                'users' => ['alfa.admin'],
            ],
            'enseñanza' => [
                'class' => Training::class, 'field' => 'name', 'form' => 'training',
                'new' => '/centro/ensenanza/nuevo', 'edit' => '/centro/ensenanza/{id}',
                'delete' => "/centro/ensenanza/eliminar/$year", 'data' => ['internalCode' => 'ZZP', 'department' => $department],
                'users' => ['alfa.admin'],
            ],
            'nivel' => [
                'class' => Grade::class, 'field' => 'name', 'form' => 'grade',
                'new' => '/centro/nivel/nuevo', 'edit' => '/centro/nivel/{id}',
                'delete' => "/centro/nivel/eliminar/$year", 'data' => ['internalCode' => 'ZZN'],
                'users' => ['alfa.admin'],
            ],
            'grupo' => [
                'class' => Group::class, 'field' => 'name', 'form' => 'group',
                'new' => '/centro/grupo/nuevo', 'edit' => '/centro/grupo/{id}',
                'delete' => "/centro/grupo/eliminar/$year", 'data' => ['internalCode' => 'ZZG'],
                'users' => ['alfa.admin'],
            ],
            'materia' => [
                'class' => Subject::class, 'field' => 'name', 'form' => 'subject',
                'new' => '/centro/materia/nuevo', 'edit' => '/centro/materia/{id}',
                'delete' => "/centro/materia/eliminar/$year", 'data' => ['code' => 'ZZM', 'internalCode' => 'ZZM'],
                'users' => ['alfa.admin'],
            ],
        ];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function entityProvider(): iterable
    {
        foreach (['día no lectivo', 'método de contacto', 'itinerario', 'escala de calificación', 'valor de escala',
                     'enseñanza', 'nivel', 'grupo', 'materia'] as $name) {
            yield $name => [$name];
        }
    }

    /**
     * Rellena los campos del formulario con el nombre y los datos mínimos de la entidad
     */
    private function fill(Form $form, string $formName, string $field, string $name, array $data): void
    {
        $form["{$formName}[{$field}]"] = $name;
        foreach ($data as $key => $value) {
            $form["{$formName}[$key]"] = $value;
        }
    }

    private function created(array $config, string $name = self::NAME): ?object
    {
        return $this->em()->getRepository($config['class'])->findOneBy([$config['field'] => $name]);
    }

    private function total(array $config): int
    {
        return $this->em()->getRepository($config['class'])->count([]);
    }

    private function create(array $config, string $name = self::NAME): object
    {
        $crawler = $this->client->request('GET', $config['new']);
        self::assertResponseIsSuccessful($config['new']);
        $form = $crawler->selectButton('Guardar cambios')->form();
        $this->fill($form, $config['form'], $config['field'], $name, $config['data']);
        $this->client->submit($form);
        self::assertResponseRedirects(null, null, 'El alta debería redirigir al listado');
        $entity = $this->created($config, $name);
        self::assertNotNull($entity, 'La entidad creada debería existir');

        return $entity;
    }

    /**
     * @dataProvider entityProvider
     */
    public function testAdministratorCreatesAndEditsTheRecord(string $entity): void
    {
        $config = $this->entities()[$entity];
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->total($config);

        $created = $this->create($config);

        self::assertSame($before + 1, $this->total($config));
        $crawler = $this->client->request('GET', str_replace('{id}', (string) $created->getId(), $config['edit']));
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(["{$config['form']}[{$config['field']}]" => self::NAME . ' editado']));
        self::assertResponseRedirects();
        // la redirección al listado debe llevar a una página que se pueda abrir
        $this->client->followRedirect();
        self::assertResponseIsSuccessful('El listado al que se redirige tras guardar se abre');
        self::assertNotNull($this->created($config, self::NAME . ' editado'));
        self::assertNull($this->created($config), 'Se modifica el registro, no se crea otro');
    }

    /**
     * @dataProvider entityProvider
     */
    public function testRecordWithoutNameIsRejected(string $entity): void
    {
        $config = $this->entities()[$entity];
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->total($config);

        $crawler = $this->client->request('GET', $config['new']);
        $form = $crawler->selectButton('Guardar cambios')->form();
        // la descripción de un día no lectivo es opcional: lo obligatorio es la fecha
        if ($entity === 'día no lectivo') {
            $this->fill($form, $config['form'], $config['field'], self::NAME, ['date' => '']);
        } else {
            $this->fill($form, $config['form'], $config['field'], '', $config['data']);
        }
        $this->client->submit($form);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertSame($before, $this->total($config));
    }

    /**
     * @dataProvider entityProvider
     */
    public function testDeletionRequiresConfirmation(string $entity): void
    {
        $config = $this->entities()[$entity];
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $id = $this->create($config)->getId();

        $this->client->request('POST', $config['delete'], ['items' => [$id]]);
        self::assertResponseIsSuccessful();
        self::assertNotNull($this->created($config), 'Sin confirmar no se borra');

        $this->client->request('POST', $config['delete'], ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertNull($this->created($config));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function outsiderProvider(): iterable
    {
        foreach (['día no lectivo', 'método de contacto', 'itinerario', 'escala de calificación', 'valor de escala',
                     'enseñanza', 'nivel', 'grupo', 'materia'] as $entity) {
            foreach (['tutor', 'profesor', 'tutorlaboral', 'alumno1', 'coordinador'] as $role) {
                yield "$entity ($role)" => [$entity, 'alfa.' . $role];
            }
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotCreateChangeOrDelete(string $entity, string $username): void
    {
        $config = $this->entities()[$entity];
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $created = $this->create($config);
        $id = $created->getId();
        $before = $this->total($config);
        $editUrl = str_replace('{id}', (string) $id, $config['edit']);

        $this->login($this->getUser($username));
        $this->client->request('GET', $config['new']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', $editUrl);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', $config['delete'], ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame($before, $this->total($config));
        self::assertNotNull($this->created($config));
    }

    /**
     * @dataProvider entityProvider
     */
    public function testAdministratorOfAnotherOrganizationCannotTouchTheRecord(string $entity): void
    {
        $config = $this->entities()[$entity];
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $id = $this->create($config)->getId();
        $before = $this->total($config);

        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->client->request('GET', str_replace('{id}', (string) $id, $config['edit']));
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', $config['delete'], ['items' => [$id], 'confirm' => 'ok']);

        self::assertSame($before, $this->total($config));
        self::assertNotNull($this->created($config));
    }

    public function testFinancialManagerManagesTravelRoutes(): void
    {
        $config = $this->entities()['itinerario'];
        $this->login($this->getUser('alfa.economico'));

        $created = $this->create($config);
        $this->client->request('POST', $config['delete'], ['items' => [$created->getId()], 'confirm' => 'ok']);

        self::assertResponseRedirects();
        self::assertNull($this->created($config));
    }
}
