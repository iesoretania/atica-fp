<?php

namespace App\Tests\Functional;

use App\Entity\WltModule\Agreement;
use App\Entity\WltModule\AgreementActivityRealization;
use App\Entity\WltModule\AgreementActivityRealizationComment;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Evaluación de las realizaciones de actividades de la FP dual: quién puede ver y calificar, comentarios
 * y su borrado. Los acuerdos de las fixtures son:
 *  - alumno5: acuerdo 1 (tutor docente y de grupo "tutor", tutor laboral "tutorlaboral")
 *  - alumno6: acuerdo 2 (tutor docente "profesor", tutor laboral "tutorlaboral2")
 */
class WltEvaluationTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function agreementOf(string $username): Agreement
    {
        return $this->em()->getRepository(Agreement::class)->createQueryBuilder('a')
            ->join('a.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->setParameter('username', $username)
            ->getQuery()->getSingleResult();
    }

    private function evaluationUrl(string $username = 'alfa.alumno5'): string
    {
        return '/dual/convenio/evaluar/' . $this->agreementOf($username)->getId();
    }

    /**
     * Realización con un comentario escrito por el tutor de grupo
     */
    private function commentedRealization(string $username = 'alfa.alumno5'): AgreementActivityRealization
    {
        return $this->em()->getRepository(AgreementActivityRealizationComment::class)->createQueryBuilder('c')
            ->join('c.agreementActivityRealization', 'r')
            ->where('r.agreement = :agreement')->setParameter('agreement', $this->agreementOf($username))
            ->setMaxResults(1)->getQuery()->getSingleResult()->getAgreementActivityRealization();
    }

    private function commentOf(AgreementActivityRealization $realization): AgreementActivityRealizationComment
    {
        return $this->em()->getRepository(AgreementActivityRealizationComment::class)
            ->findOneBy(['agreementActivityRealization' => $realization->getId()]);
    }

    private function commentExists(int $id): bool
    {
        return $this->em()->getRepository(AgreementActivityRealizationComment::class)->find($id) instanceof AgreementActivityRealizationComment;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function viewersProvider(): iterable
    {
        yield 'tutor de grupo' => ['alfa.tutor'];
        yield 'tutor laboral' => ['alfa.tutorlaboral'];
        yield 'coordinador' => ['alfa.coordinador'];
        yield 'jefe de departamento' => ['alfa.jefe'];
        yield 'administrador local' => ['alfa.admin'];
    }

    /**
     * @dataProvider viewersProvider
     */
    public function testAuthorizedPeopleSeeTheEvaluation(string $username): void
    {
        $this->login($this->getUser($username));

        $this->client->request('GET', $this->evaluationUrl());

        self::assertResponseIsSuccessful();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        yield 'el propio alumno' => ['alfa.alumno5'];
        yield 'alumno de otro acuerdo' => ['alfa.alumno6'];
        yield 'tutor laboral de otro acuerdo' => ['alfa.tutorlaboral2'];
        yield 'alumno de FCT' => ['alfa.alumno1'];
        yield 'responsable económico' => ['alfa.economico'];
        yield 'administrador local de otro centro' => ['beta.admin'];
        yield 'coordinador de otro centro' => ['beta.coordinador'];
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOutsidersCannotSeeOrGradeTheEvaluation(string $username): void
    {
        $url = $this->evaluationUrl();
        $realization = $this->commentedRealization();
        $this->login($this->getUser($username));

        $this->client->request('GET', $url);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', $url, ['agreement_evaluation' => ['workTutorRemarks' => 'Observaciones ajenas']]);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/dual/convenio/evaluar/comentarios/' . $realization->getId());
        self::assertResponseStatusCodeSame(403);

        self::assertNotSame('Observaciones ajenas', $this->agreementOf('alfa.alumno5')->getWorkTutorRemarks());
    }

    public function testGroupTutorSavesTheRemarks(): void
    {
        $this->login($this->getUser('alfa.tutor'));

        $crawler = $this->client->request('GET', $this->evaluationUrl());
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'agreement_evaluation[workTutorRemarks]' => 'Buen trabajo en la empresa',
        ]));

        self::assertResponseRedirects();
        self::assertSame('Buen trabajo en la empresa', $this->agreementOf('alfa.alumno5')->getWorkTutorRemarks());
    }

    public function testWorkTutorCanGrade(): void
    {
        $this->login($this->getUser('alfa.tutorlaboral'));

        $crawler = $this->client->request('GET', $this->evaluationUrl());
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'agreement_evaluation[workTutorRemarks]' => 'Evaluación del tutor laboral',
        ]));

        self::assertResponseRedirects();
        self::assertSame('Evaluación del tutor laboral', $this->agreementOf('alfa.alumno5')->getWorkTutorRemarks());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function gradersProvider(): iterable
    {
        yield 'tutor de grupo' => ['alfa.tutor'];
        yield 'tutor laboral' => ['alfa.tutorlaboral'];
        yield 'coordinador' => ['alfa.coordinador'];
        yield 'jefe de departamento' => ['alfa.jefe'];
    }

    /**
     * Con el proyecto cerrado nadie que valora puede guardar la valoración, ni mandando la petición completa del
     * formulario (preparada con el proyecto abierto)
     *
     * @dataProvider gradersProvider
     */
    public function testNothingIsSavedWhenTheProjectIsLocked(string $username): void
    {
        $agreement = $this->agreementOf('alfa.alumno5');
        $before = $agreement->getWorkTutorRemarks();
        $this->login($this->getUser($username));

        // la petición se prepara con el formulario real mientras el proyecto está abierto...
        $crawler = $this->client->request('GET', $this->evaluationUrl());
        $values = $crawler->selectButton('Guardar cambios')->form()->getPhpValues();
        $values['agreement_evaluation']['workTutorRemarks'] = 'Cambio con el proyecto cerrado';

        // ... y se envía una vez cerrado
        $em = $this->em();
        $em->find(Agreement::class, $agreement->getId())->getProject()->setLocked(true);
        $em->flush();
        $this->client->request('POST', $this->evaluationUrl(), $values);
        self::assertSame($before, $this->agreementOf('alfa.alumno5')->getWorkTutorRemarks(), $username);

        // control: la misma petición, con el proyecto abierto, sí guarda (si no, la prueba no sería concluyente)
        $em = $this->em();
        $em->find(Agreement::class, $agreement->getId())->getProject()->setLocked(false);
        $em->flush();
        $this->client->request('POST', $this->evaluationUrl(), $values);
        self::assertSame('Cambio con el proyecto cerrado', $this->agreementOf('alfa.alumno5')->getWorkTutorRemarks(), $username);
    }

    public function testRemarksOfOtherAgreementsAreNotModified(): void
    {
        $this->login($this->getUser('alfa.tutor'));
        $other = $this->agreementOf('alfa.alumno6');

        // el tutor del grupo del acuerdo 1 no es el docente del acuerdo 2 pero sí tutor del grupo: solo se comprueba
        // que la petición sobre su acuerdo no toca el ajeno
        $crawler = $this->client->request('GET', $this->evaluationUrl());
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'agreement_evaluation[workTutorRemarks]' => 'Solo para el acuerdo 1',
        ]));

        self::assertSame($other->getWorkTutorRemarks(), $this->agreementOf('alfa.alumno6')->getWorkTutorRemarks());
    }

    public function testGroupTutorAddsAComment(): void
    {
        $realization = $this->commentedRealization();
        $before = $this->em()->getRepository(AgreementActivityRealizationComment::class)->count([]);
        $this->login($this->getUser('alfa.tutor'));

        $crawler = $this->client->request('GET', '/dual/convenio/evaluar/comentarios/' . $realization->getId());
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'agreement_activity_realization_new_comment[newComment]' => '  Comentario nuevo del tutor  ',
        ]));

        self::assertResponseRedirects();
        self::assertSame($before + 1, $this->em()->getRepository(AgreementActivityRealizationComment::class)->count([]));
        $comment = $this->em()->getRepository(AgreementActivityRealizationComment::class)
            ->findOneBy(['comment' => 'Comentario nuevo del tutor']);
        self::assertNotNull($comment, 'El comentario se guarda sin espacios sobrantes');
        self::assertSame($this->getUser('alfa.tutor')->getId(), $comment->getPerson()->getId());
    }

    public function testStudentCannotAddComments(): void
    {
        $realization = $this->commentedRealization();
        $before = $this->em()->getRepository(AgreementActivityRealizationComment::class)->count([]);
        $this->login($this->getUser('alfa.alumno5'));

        $this->client->request('POST', '/dual/convenio/evaluar/comentarios/' . $realization->getId(), [
            'agreement_activity_realization_new_comment' => ['newComment' => 'Comentario del alumno'],
        ]);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($before, $this->em()->getRepository(AgreementActivityRealizationComment::class)->count([]));
    }

    // --- Borrado de comentarios ----------------------------------------------------------------

    public function testCommentIsNotDeletedByALink(): void
    {
        $comment = $this->commentOf($this->commentedRealization());
        $url = '/dual/convenio/evaluar/comentarios/eliminar/' . $comment->getId();
        $this->login($this->getUser('alfa.tutor'));

        // un enlace (GET) solo puede mostrar la confirmación, nunca borrar
        $this->client->request('GET', $url . '?confirm=ok');
        self::assertResponseIsSuccessful();
        self::assertTrue($this->commentExists($comment->getId()));
    }

    public function testAuthorDeletesTheirCommentWithConfirmation(): void
    {
        $comment = $this->commentOf($this->commentedRealization());
        $url = '/dual/convenio/evaluar/comentarios/eliminar/' . $comment->getId();
        $this->login($this->getUser('alfa.tutor'));

        $this->client->request('POST', $url);
        self::assertResponseIsSuccessful();
        self::assertTrue($this->commentExists($comment->getId()), 'Sin confirmar no se borra');

        $this->client->request('POST', $url, ['confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertFalse($this->commentExists($comment->getId()));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function cannotDeleteProvider(): iterable
    {
        yield 'tutor laboral' => ['alfa.tutorlaboral'];
        yield 'alumno' => ['alfa.alumno5'];
        yield 'profesor de otro acuerdo' => ['alfa.profesor'];
        yield 'coordinador de otro centro' => ['beta.coordinador'];
    }

    /**
     * @dataProvider cannotDeleteProvider
     */
    public function testOthersCannotDeleteTheComment(string $username): void
    {
        $comment = $this->commentOf($this->commentedRealization());
        $this->login($this->getUser($username));

        $this->client->request('POST', '/dual/convenio/evaluar/comentarios/eliminar/' . $comment->getId(), ['confirm' => 'ok']);

        self::assertResponseStatusCodeSame(403);
        self::assertTrue($this->commentExists($comment->getId()));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function managersProvider(): iterable
    {
        yield 'coordinador' => ['alfa.coordinador'];
        yield 'jefe de departamento' => ['alfa.jefe'];
        yield 'administrador local' => ['alfa.admin'];
    }

    /**
     * @dataProvider managersProvider
     */
    public function testManagersCanDeleteAnyComment(string $username): void
    {
        $id = $this->commentOf($this->commentedRealization())->getId();
        $this->login($this->getUser($username));

        $this->client->request('POST', '/dual/convenio/evaluar/comentarios/eliminar/' . $id, ['confirm' => 'ok']);

        self::assertResponseRedirects();
        self::assertFalse($this->commentExists($id));
    }
}
