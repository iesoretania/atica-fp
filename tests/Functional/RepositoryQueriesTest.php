<?php

namespace App\Tests\Functional;

use App\Entity\Edu\AcademicYear;
use App\Entity\Organization;
use App\Entity\Person;
use App\Tests\Fixtures\DemoData;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Ejecuta cada método público de cada repositorio con argumentos obtenidos de las fixtures. Una consulta DQL o
 * SQL incorrecta (un campo que ya no existe, una unión mal escrita) solo falla al ejecutarla, por lo que se
 * detecta aquí aunque ninguna pantalla la use en los tests. Los métodos que modifican datos se ejecutan dentro de
 * la transacción del test, que se revierte; las violaciones de integridad no se consideran un error de consulta.
 */
class RepositoryQueriesTest extends KernelTestCase
{
    use EnablesSurveysTrait {
        em as private surveysEntityManager;
    }

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        // las fixtures no tienen encuestas: se crean para poder ejecutar los métodos que las usan
        $this->enableSurveys();
        $this->em = static::getContainer()->get('doctrine')->getManager();
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function methodProvider(): iterable
    {
        $base = dirname(__DIR__, 2) . '/src/Repository';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
        $classes = [];
        foreach ($files as $file) {
            if (!str_ends_with($file->getFilename(), 'Repository.php')) {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($base) + 1, -4);
            $classes[] = 'App\\Repository\\' . str_replace('/', '\\', $relative);
        }
        sort($classes);

        foreach ($classes as $class) {
            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract()) {
                continue;
            }
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $class || $method->isConstructor() || $method->isStatic()) {
                    continue;
                }
                yield substr($class, strlen('App\\Repository\\')) . '::' . $method->getName() => [$class, $method->getName()];
            }
        }
    }

    /**
     * Primera fila de la entidad (las de la organización Alfa se cargan antes que las de Beta)
     */
    private function first(string $class): ?object
    {
        if ($class === Person::class) {
            return $this->em->getRepository(Person::class)->findOneBy(['loginUsername' => 'alfa.tutor']);
        }
        if ($class === Organization::class) {
            return $this->em->getRepository(Organization::class)->findOneBy(['code' => DemoData::ORG_A_CODE]);
        }
        if ($class === AcademicYear::class) {
            return $this->em->getRepository(Organization::class)->findOneBy(['code' => DemoData::ORG_A_CODE])->getCurrentAcademicYear();
        }
        try {
            return $this->em->getRepository($class)->findOneBy([], ['id' => 'ASC']);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{bool, mixed} si se ha podido resolver y el valor
     */
    private function argument(\ReflectionParameter $parameter): array
    {
        $type = $parameter->getType();
        $name = $parameter->getName();
        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();
            if (!$type->isBuiltin() && class_exists($typeName)) {
                if (is_a($typeName, \DateTimeInterface::class, true)) {
                    return [true, new \DateTime('today')];
                }
                if (in_array($typeName, [ArrayCollection::class, \Doctrine\Common\Collections\Collection::class], true)) {
                    return [true, new ArrayCollection()];
                }
                $entity = $this->first($typeName);
                if ($entity !== null) {
                    return [true, $entity];
                }

                return $type->allowsNull() ? [true, null] : [false, null];
            }
            switch ($typeName) {
                case 'int':
                    return [true, 1];
                case 'float':
                    return [true, 1.0];
                case 'bool':
                    return [true, false];
                case 'string':
                    return [true, 'a'];
                case 'array':
                    return [true, []];
            }
        }
        if ($parameter->isDefaultValueAvailable()) {
            return [true, $parameter->getDefaultValue()];
        }

        // parámetros sin tipo: se deducen por su nombre
        $byName = [
            'items' => [1], 'list' => [1], 'ids' => [1],
            'id' => 1, 'week' => 1, 'year' => 2026, 'value' => 1, 'number' => 1,
            'code' => 'a', 'q' => 'a', 'internalCode' => 'a', 'name' => 'a', 'text' => 'a', 'username' => 'alfa.tutor',
        ];
        if (array_key_exists($name, $byName)) {
            return [true, $byName[$name]];
        }
        if (in_array($name, ['date', 'startDate', 'endDate', 'dateTime', 'start', 'end'], true)) {
            return [true, new \DateTime('today')];
        }
        $collections = [
            'groups' => \App\Entity\Edu\Group::class, 'teachers' => \App\Entity\Edu\Teacher::class,
            'projects' => \App\Entity\WltModule\Project::class, 'workcenters' => \App\Entity\Workcenter::class,
            'trainingPrograms' => \App\Entity\ItpModule\TrainingProgram::class,
            'persons' => Person::class, 'students' => \App\Entity\Edu\StudentEnrollment::class,
        ];
        if (isset($collections[$name])) {
            $entity = $this->first($collections[$name]);

            return [true, $entity === null ? [] : [$entity]];
        }

        return [false, null];
    }

    /**
     * @dataProvider methodProvider
     */
    public function testEveryRepositoryMethodRunsItsQuery(string $class, string $methodName): void
    {
        $repository = static::getContainer()->get($class);
        $method = new \ReflectionMethod($class, $methodName);
        $arguments = [];
        foreach ($method->getParameters() as $parameter) {
            [$resolved, $value] = $this->argument($parameter);
            if (!$resolved) {
                self::markTestSkipped(sprintf('No se puede construir el argumento $%s', $parameter->getName()));
            }
            $arguments[] = $value;
        }

        try {
            $result = $repository->$methodName(...$arguments);
            // los constructores de consultas devuelven el QueryBuilder sin ejecutarlo: se ejecuta para validar el DQL
            if ($result instanceof \Doctrine\ORM\QueryBuilder) {
                $result->getQuery()->getResult();
            } elseif ($result instanceof \Doctrine\ORM\Query) {
                $result->getResult();
            }
            $this->addToAssertionCount(1);
        } catch (ConstraintViolationException) {
            // modifica datos que otros dependen de ellos: la consulta es correcta
            $this->addToAssertionCount(1);
        } catch (\TypeError $e) {
            self::markTestSkipped('Los argumentos de ejemplo no sirven para este método: ' . $e->getMessage());
        } catch (QueryException | DriverException $e) {
            self::fail(sprintf('%s::%s() tiene una consulta incorrecta: %s', $class, $methodName, $e->getMessage()));
        } catch (\Throwable $e) {
            // cualquier otra excepción depende de los datos de ejemplo (no hay resultado, argumentos vacíos…)
            $this->addToAssertionCount(1);
        }
    }
}
