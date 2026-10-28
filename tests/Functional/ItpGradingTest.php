<?php

namespace App\Tests\Functional;

use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\StudentProgramWorkcenterActivity;
use App\Entity\ItpModule\StudentProgramWorkcenterActivityComment;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Calificación de las estancias de la FFE: quién puede ver y calificar, y comentarios de las actividades.
 *  - alumno7: tutor docente "profesor", tutor laboral "tutorlaboral"
 *  - alumno8: tutor docente "tutor", tutor laboral "tutorlaboral2"
 * El gestor de la FFE ("gestorffe") gestiona el grupo de ambos.
 */
class ItpGradingTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function stayOf(string $username): StudentProgramWorkcenter
    {
        return $this->em()->getRepository(StudentProgramWorkcenter::class)->createQueryBuilder('s')
            ->join('s.studentProgram', 'sp')->join('sp.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->setParameter('username', $username)
            ->getQuery()->getSingleResult();
    }

    private function gradingUrl(string $username = 'alfa.alumno7'): string
    {
        return '/formacion/valoracion/' . $this->stayOf($username)->getId();
    }

    private function activityOf(string $username = 'alfa.alumno7'): StudentProgramWorkcenterActivity
    {
        return $this->em()->getRepository(StudentProgramWorkcenterActivity::class)
            ->findOneBy(['studentProgramWorkcenter' => $this->stayOf($username)->getId()]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function gradersProvider(): iterable
    {
        yield 'tutor docente' => ['alfa.profesor'];
        yield 'tutor laboral' => ['alfa.tutorlaboral'];
        yield 'gestor de la FFE' => ['alfa.gestorffe'];
        yield 'jefe de departamento' => ['alfa.jefe'];
        yield 'administrador local' => ['alfa.admin'];
    }

    /**
     * @dataProvider gradersProvider
     */
    public function testAuthorizedPeopleCanSaveTheGrading(string $username): void
    {
        $this->login($this->getUser($username));

        $crawler = $this->client->request('GET', $this->gradingUrl());
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'student_program_workcenter_grade[workTutorRemarks]' => 'Observaciones de ' . $username,
        ]));

        self::assertResponseRedirects();
        self::assertSame('Observaciones de ' . $username, $this->stayOf('alfa.alumno7')->getWorkTutorRemarks());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        yield 'el propio alumno' => ['alfa.alumno7'];
        yield 'alumno de otra estancia' => ['alfa.alumno8'];
        yield 'tutor laboral de otra estancia' => ['alfa.tutorlaboral2'];
        yield 'alumno de FCT' => ['alfa.alumno1'];
        yield 'responsable económico' => ['alfa.economico'];
        yield 'coordinador de FP dual' => ['alfa.coordinador'];
        yield 'administrador local de otro centro' => ['beta.admin'];
        yield 'gestor de la FFE de otro centro' => ['beta.gestorffe'];
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOutsidersCannotSeeOrGrade(string $username): void
    {
        $url = $this->gradingUrl();
        $activity = $this->activityOf();
        $this->login($this->getUser($username));

        $this->client->request('GET', $url);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', $url, ['student_program_workcenter_grade' => ['workTutorRemarks' => 'Observaciones ajenas']]);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/formacion/valoracion/comentarios/' . $activity->getId());
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/formacion/valoracion/informe/' . $this->stayOf('alfa.alumno7')->getId());
        self::assertResponseStatusCodeSame(403);

        self::assertNotSame('Observaciones ajenas', $this->stayOf('alfa.alumno7')->getWorkTutorRemarks());
    }

    public function testTutorOfAnotherStayCannotGradeThisOne(): void
    {
        // el tutor laboral de la estancia de alumno8 solo modifica esa estancia
        $this->login($this->getUser('alfa.tutorlaboral2'));

        $crawler = $this->client->request('GET', $this->gradingUrl('alfa.alumno8'));
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'student_program_workcenter_grade[workTutorRemarks]' => 'Observaciones del tutor laboral',
        ]));

        self::assertResponseRedirects();
        self::assertSame('Observaciones del tutor laboral', $this->stayOf('alfa.alumno8')->getWorkTutorRemarks());
        self::assertNotSame('Observaciones del tutor laboral', $this->stayOf('alfa.alumno7')->getWorkTutorRemarks());
    }

    public function testAuthorizedPersonAddsAComment(): void
    {
        $activity = $this->activityOf();
        $before = $this->em()->getRepository(StudentProgramWorkcenterActivityComment::class)->count([]);
        $this->login($this->getUser('alfa.profesor'));

        $url = '/formacion/valoracion/comentarios/' . $activity->getId();
        $crawler = $this->client->request('GET', $url);
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'student_program_workcenter_activity_new_comment[newComment]' => '  Comentario nuevo  ',
        ]));

        // tras añadir un comentario se vuelve a la pantalla de comentarios
        self::assertResponseRedirects($url);
        self::assertSame($before + 1, $this->em()->getRepository(StudentProgramWorkcenterActivityComment::class)->count([]));
        self::assertNotNull($this->em()->getRepository(StudentProgramWorkcenterActivityComment::class)->findOneBy(['comment' => 'Comentario nuevo']));
    }

    public function testStudentCannotAddComments(): void
    {
        $activity = $this->activityOf();
        $before = $this->em()->getRepository(StudentProgramWorkcenterActivityComment::class)->count([]);
        $this->login($this->getUser('alfa.alumno7'));

        $this->client->request('POST', '/formacion/valoracion/comentarios/' . $activity->getId(), [
            'student_program_workcenter_activity_new_comment' => ['newComment' => 'Comentario del alumno'],
        ]);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($before, $this->em()->getRepository(StudentProgramWorkcenterActivityComment::class)->count([]));
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function commentDeletionProvider(): iterable
    {
        yield 'autor' => ['alfa.profesor', 302];
        yield 'jefe de departamento' => ['alfa.jefe', 302];
        yield 'administrador local' => ['alfa.admin', 302];
        yield 'tutor laboral' => ['alfa.tutorlaboral', 403];
        yield 'alumno' => ['alfa.alumno7', 403];
        yield 'gestor de la FFE de otro centro' => ['beta.gestorffe', 403];
    }

    /**
     * @dataProvider commentDeletionProvider
     */
    public function testCommentDeletionPermissions(string $username, int $status): void
    {
        $id = $this->em()->getRepository(StudentProgramWorkcenterActivityComment::class)
            ->findOneBy(['person' => $this->getUser('alfa.profesor')->getId()])->getId();
        $this->login($this->getUser($username));

        $this->client->request('POST', '/formacion/valoracion/comentarios/eliminar/' . $id, ['confirm' => 'ok']);

        self::assertResponseStatusCodeSame($status);
        $exists = $this->em()->getRepository(StudentProgramWorkcenterActivityComment::class)->find($id) !== null;
        self::assertSame($status === 403, $exists);
    }

    public function testReportIsGenerated(): void
    {
        $this->login($this->getUser('alfa.profesor'));

        $this->client->request('GET', '/formacion/valoracion/informe/' . $this->stayOf('alfa.alumno7')->getId());

        // sin plantilla de informe configurada se avisa en lugar de fallar
        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
    }
}
