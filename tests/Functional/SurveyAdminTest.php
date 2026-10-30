<?php

namespace App\Tests\Functional;

use App\Entity\AnsweredSurvey;
use App\Entity\AnsweredSurveyQuestion;
use App\Entity\Organization;
use App\Entity\Survey;
use App\Entity\SurveyQuestion;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Administración de encuestas del centro: alta y edición de encuestas, preguntas, orden, borrado y
 * eliminación de las respuestas. Las fixtures no incluyen encuestas, por lo que cada test crea las suyas.
 */
class SurveyAdminTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    /**
     * @return array{int, list<int>} identificador de la encuesta y de sus tres preguntas, por orden
     */
    private function createSurvey(string $code, string $title, bool $answered = false): array
    {
        $em = $this->em();
        $survey = (new Survey())
            ->setOrganization($em->getRepository(Organization::class)->findOneBy(['code' => $code]))
            ->setTitle($title);
        $em->persist($survey);
        $questions = [];
        foreach ([1, 2, 3] as $order) {
            $question = (new SurveyQuestion())
                ->setSurvey($survey)->setDescription("Pregunta $order")->setType(SurveyQuestion::RANGE_1_5)
                ->setMandatory(false)->setOrderNr($order);
            $em->persist($question);
            $questions[] = $question;
        }
        if ($answered) {
            $answeredSurvey = (new AnsweredSurvey())->setSurvey($survey)->setTimestamp(new \DateTime());
            $em->persist($answeredSurvey);
            $em->persist((new AnsweredSurveyQuestion())
                ->setAnsweredSurvey($answeredSurvey)->setSurveyQuestion($questions[0])->setNumericValue(3));
        }
        $em->flush();
        $ids = [$survey->getId(), array_map(static fn(SurveyQuestion $q): int => $q->getId(), $questions)];
        // las colecciones inversas de las entidades recién creadas no se actualizan: se descartan para que la
        // aplicación lea los datos desde la base de datos
        $em->clear();

        return $ids;
    }

    private function surveyExists(int $id): bool
    {
        return $this->em()->getRepository(Survey::class)->find($id) instanceof Survey;
    }

    /**
     * @return list<int> identificadores de las preguntas de la encuesta, por orden
     */
    private function questionIds(int $surveyId): array
    {
        return array_map(
            static fn(SurveyQuestion $q): int => $q->getId(),
            $this->em()->getRepository(SurveyQuestion::class)->findBy(['survey' => $surveyId], ['orderNr' => 'ASC'])
        );
    }

    private function answerCount(): int
    {
        return $this->em()->getRepository(AnsweredSurvey::class)->count([]);
    }

    // --- Permisos ----------------------------------------------------------------------------------

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonManagerProvider(): iterable
    {
        foreach (['jefe', 'tutor', 'profesor', 'tutorlaboral', 'alumno1', 'coordinador', 'gestorffe', 'economico'] as $role) {
            yield $role => ['alfa.' . $role];
        }
        yield 'profesorado de otro centro' => ['beta.profesor'];
    }

    /**
     * @dataProvider nonManagerProvider
     */
    public function testNonManagersCannotAdministerSurveys(string $username): void
    {
        [$surveyId, $questions] = $this->createSurvey(DemoData::ORG_A_CODE, 'Encuesta del centro');
        $this->login($this->getUser($username), true, str_starts_with($username, 'beta.') ? DemoData::ORG_B_CODE : DemoData::ORG_A_CODE);
        $before = $this->em()->getRepository(Survey::class)->count([]);

        foreach (['GET /centro/encuesta/listar', 'GET /centro/encuesta/nueva', "GET /centro/encuesta/$surveyId",
                     "GET /centro/encuesta/pregunta/nueva/$surveyId", "GET /centro/encuesta/pregunta/{$questions[0]}",
                     "GET /centro/encuesta/pregunta/listar/$surveyId/resultado"] as $request) {
            [$method, $path] = explode(' ', $request);
            $this->client->request($method, $path);
            self::assertResponseStatusCodeSame(403, $request);
        }
        $this->client->request('POST', '/centro/encuesta/operacion', ['items' => [$surveyId], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', "/centro/encuesta/pregunta/operacion/$surveyId", ['items' => [$questions[0]], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertTrue($this->surveyExists($surveyId));
        self::assertSame($before, $this->em()->getRepository(Survey::class)->count([]));
        self::assertCount(3, $this->questionIds($surveyId));
    }

    // --- Encuestas ---------------------------------------------------------------------------------

    public function testAdministratorCreatesAndEditsASurvey(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', '/centro/encuesta/nueva');
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['survey[title]' => 'Satisfacción del alumnado']));
        self::assertResponseRedirects('/centro/encuesta/listar');

        $survey = $this->em()->getRepository(Survey::class)->findOneBy(['title' => 'Satisfacción del alumnado']);
        self::assertInstanceOf(Survey::class, $survey);
        self::assertSame($this->getOrganization()->getId(), $survey->getOrganization()->getId());

        $crawler = $this->client->request('GET', '/centro/encuesta/' . $survey->getId());
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['survey[title]' => 'Satisfacción renombrada']));
        self::assertResponseRedirects();
        self::assertSame('Satisfacción renombrada', $this->em()->getRepository(Survey::class)->find($survey->getId())->getTitle());
    }

    public function testSurveyWithoutTitleIsRejected(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->em()->getRepository(Survey::class)->count([]);

        $crawler = $this->client->request('GET', '/centro/encuesta/nueva');
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['survey[title]' => '']));

        self::assertResponseStatusCodeSame(200);
        self::assertSame($before, $this->em()->getRepository(Survey::class)->count([]));
    }

    public function testNewSurveyCanCopyTheQuestionsOfAnotherOne(): void
    {
        [$originalId] = $this->createSurvey(DemoData::ORG_A_CODE, 'Original');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', '/centro/encuesta/nueva');
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'survey[title]' => 'Copia', 'survey[copyFrom]' => (string) $originalId,
        ]));

        $copy = $this->em()->getRepository(Survey::class)->findOneBy(['title' => 'Copia']);
        self::assertNotNull($copy);
        self::assertCount(3, $this->questionIds($copy->getId()));
        self::assertCount(3, $this->questionIds($originalId), 'La encuesta original no cambia');
    }

    public function testSurveyOfAnotherOrganizationCannotBeCopiedOrOpened(): void
    {
        [$foreignId] = $this->createSurvey(DemoData::ORG_B_CODE, 'Encuesta de Beta');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', '/centro/encuesta/nueva');
        $form = $crawler->selectButton('Guardar cambios')->form();
        $values = $form->getPhpValues();
        $values['survey']['title'] = 'Copia ajena';
        $values['survey']['copyFrom'] = (string) $foreignId;
        $this->client->request('POST', '/centro/encuesta/nueva', $values);

        self::assertNull($this->em()->getRepository(Survey::class)->findOneBy(['title' => 'Copia ajena']));

        $this->client->request('GET', "/centro/encuesta/$foreignId");
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', "/centro/encuesta/pregunta/nueva/$foreignId");
        self::assertResponseStatusCodeSame(403);
    }

    public function testDeletingASurveyRequiresConfirmationAndRemovesItsQuestions(): void
    {
        [$surveyId] = $this->createSurvey(DemoData::ORG_A_CODE, 'Para borrar');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', '/centro/encuesta/operacion', ['items' => [$surveyId]]);
        self::assertResponseIsSuccessful();
        self::assertTrue($this->surveyExists($surveyId), 'Sin confirmar no se borra');

        $this->client->request('POST', '/centro/encuesta/operacion', ['items' => [$surveyId], 'confirm' => 'ok']);
        self::assertResponseRedirects('/centro/encuesta/listar');
        self::assertFalse($this->surveyExists($surveyId));
        self::assertSame([], $this->questionIds($surveyId));
    }

    public function testSurveyWithAnswersIsNotDeletedUntilPurged(): void
    {
        [$surveyId] = $this->createSurvey(DemoData::ORG_A_CODE, 'Con respuestas', true);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', '/centro/encuesta/operacion', ['items' => [$surveyId], 'confirm' => 'ok']);
        self::assertTrue($this->surveyExists($surveyId), 'Una encuesta con respuestas no se borra');
        self::assertSame(1, $this->answerCount());

        // vaciar las respuestas pide confirmación y deja la encuesta
        $this->client->request('POST', '/centro/encuesta/operacion', ['items' => [$surveyId], 'purge' => '1']);
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->answerCount());
        $this->client->request('POST', '/centro/encuesta/operacion', ['items' => [$surveyId], 'purge' => '1', 'confirm' => 'ok']);
        self::assertResponseRedirects('/centro/encuesta/listar');
        self::assertSame(0, $this->answerCount());
        self::assertTrue($this->surveyExists($surveyId));
    }

    public function testSurveyOfAnotherOrganizationCannotBeDeletedOrPurged(): void
    {
        [$foreignId] = $this->createSurvey(DemoData::ORG_B_CODE, 'Encuesta de Beta', true);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', '/centro/encuesta/operacion', ['items' => [$foreignId], 'purge' => '1', 'confirm' => 'ok']);
        $this->client->request('POST', '/centro/encuesta/operacion', ['items' => [$foreignId], 'confirm' => 'ok']);

        self::assertTrue($this->surveyExists($foreignId));
        self::assertSame(1, $this->answerCount(), 'Las respuestas de otro centro no se vacían');
    }

    // --- Preguntas ---------------------------------------------------------------------------------

    public function testAdministratorAddsAndEditsAQuestion(): void
    {
        [$surveyId, $questions] = $this->createSurvey(DemoData::ORG_A_CODE, 'Encuesta');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', "/centro/encuesta/pregunta/nueva/$surveyId");
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'survey_question[description]' => 'Comentarios finales',
            'survey_question[type]' => SurveyQuestion::TEXTAREA,
            'survey_question[mandatory]' => '1',
        ]));
        self::assertResponseRedirects();

        $ids = $this->questionIds($surveyId);
        self::assertCount(4, $ids);
        $new = $this->em()->getRepository(SurveyQuestion::class)->find(end($ids));
        self::assertSame('Comentarios finales', $new->getDescription());
        self::assertSame(SurveyQuestion::TEXTAREA, $new->getType());
        self::assertSame(4, $new->getOrderNr(), 'La nueva pregunta se añade al final');

        $crawler = $this->client->request('GET', "/centro/encuesta/pregunta/{$questions[0]}");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['survey_question[description]' => 'Pregunta modificada']));
        self::assertResponseRedirects();
        self::assertSame('Pregunta modificada', $this->em()->getRepository(SurveyQuestion::class)->find($questions[0])->getDescription());
    }

    public function testQuestionsCanBeMovedUpAndDown(): void
    {
        [$surveyId, $q] = $this->createSurvey(DemoData::ORG_A_CODE, 'Encuesta');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/centro/encuesta/pregunta/operacion/$surveyId", ['up' => $q[1]]);
        self::assertSame([$q[1], $q[0], $q[2]], $this->questionIds($surveyId));

        $this->client->request('POST', "/centro/encuesta/pregunta/operacion/$surveyId", ['down' => $q[1]]);
        self::assertSame([$q[0], $q[1], $q[2]], $this->questionIds($surveyId));

        // los extremos no cambian y no fallan
        $this->client->request('POST', "/centro/encuesta/pregunta/operacion/$surveyId", ['up' => $q[0]]);
        $this->client->request('POST', "/centro/encuesta/pregunta/operacion/$surveyId", ['down' => $q[2]]);
        self::assertSame([$q[0], $q[1], $q[2]], $this->questionIds($surveyId));
    }

    public function testQuestionOfAnotherSurveyCannotBeMovedThroughThisOne(): void
    {
        [$surveyId] = $this->createSurvey(DemoData::ORG_A_CODE, 'Encuesta');
        [$otherId, $other] = $this->createSurvey(DemoData::ORG_A_CODE, 'Otra encuesta');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/centro/encuesta/pregunta/operacion/$surveyId", ['up' => $other[1]]);

        self::assertSame($other, $this->questionIds($otherId));
    }

    public function testQuestionsAreDeletedOnlyWithConfirmation(): void
    {
        [$surveyId, $q] = $this->createSurvey(DemoData::ORG_A_CODE, 'Encuesta');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/centro/encuesta/pregunta/operacion/$surveyId", ['items' => [$q[2]]]);
        self::assertResponseIsSuccessful();
        self::assertCount(3, $this->questionIds($surveyId));

        $this->client->request('POST', "/centro/encuesta/pregunta/operacion/$surveyId", ['items' => [$q[2]], 'confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertSame([$q[0], $q[1]], $this->questionIds($surveyId));
    }

    public function testQuestionsOfASurveyWithAnswersCannotBeDeleted(): void
    {
        [$surveyId, $q] = $this->createSurvey(DemoData::ORG_A_CODE, 'Con respuestas', true);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/centro/encuesta/pregunta/operacion/$surveyId", ['items' => [$q[1]], 'confirm' => 'ok']);

        self::assertCount(3, $this->questionIds($surveyId));
    }

    public function testQuestionsOfAnAnsweredSurveyCannotBeDeletedThroughAnotherSurvey(): void
    {
        [$freeId] = $this->createSurvey(DemoData::ORG_A_CODE, 'Sin respuestas');
        [$answeredId, $answered] = $this->createSurvey(DemoData::ORG_A_CODE, 'Con respuestas', true);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        // se usa una encuesta sin respuestas como pasarela para borrar preguntas de otra que sí las tiene
        $this->client->request('POST', "/centro/encuesta/pregunta/operacion/$freeId", ['items' => [$answered[1], $answered[2]], 'confirm' => 'ok']);

        self::assertSame($answered, $this->questionIds($answeredId));
        self::assertSame(1, $this->answerCount());
    }

    public function testQuestionsOfAnotherOrganizationCannotBeDeleted(): void
    {
        [$ownId] = $this->createSurvey(DemoData::ORG_A_CODE, 'Propia');
        [$foreignId, $foreign] = $this->createSurvey(DemoData::ORG_B_CODE, 'Ajena');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/centro/encuesta/pregunta/operacion/$ownId", ['items' => $foreign, 'confirm' => 'ok']);
        $this->client->request('POST', "/centro/encuesta/pregunta/operacion/$foreignId", ['items' => $foreign, 'confirm' => 'ok']);

        self::assertSame($foreign, $this->questionIds($foreignId));
    }

    public function testQuestionListShowsAndFiltersTheQuestions(): void
    {
        [$surveyId] = $this->createSurvey(DemoData::ORG_A_CODE, 'Encuesta con preguntas');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        foreach (["/centro/encuesta/pregunta/listar/$surveyId/resultado", "/centro/encuesta/pregunta/listar/$surveyId/resultado/1", "/centro/encuesta/pregunta/listar/$surveyId/resultado/99"] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($url);
            self::assertStringContainsString('Pregunta 2', (string) $this->client->getResponse()->getContent(), $url);
        }

        $this->client->request('GET', "/centro/encuesta/pregunta/listar/$surveyId/resultado", ['q' => 'Pregunta 2']);
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Pregunta 2', $content);
        self::assertStringNotContainsString('Pregunta 3', $content);
    }

    public function testQuestionListOfAnAnsweredSurveyOpens(): void
    {
        [$surveyId] = $this->createSurvey(DemoData::ORG_A_CODE, 'Con respuestas', true);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', "/centro/encuesta/pregunta/listar/$surveyId/resultado");

        self::assertResponseIsSuccessful();
    }

    /**
     * @dataProvider nonManagerProvider
     */
    public function testNonManagersCannotListTheQuestions(string $username): void
    {
        [$surveyId] = $this->createSurvey(DemoData::ORG_A_CODE, 'Privada');
        $this->login($this->getUser($username), true, str_starts_with($username, 'beta.') ? DemoData::ORG_B_CODE : DemoData::ORG_A_CODE);

        $this->client->request('GET', "/centro/encuesta/pregunta/listar/$surveyId/resultado");

        self::assertResponseStatusCodeSame(403);
    }
}
