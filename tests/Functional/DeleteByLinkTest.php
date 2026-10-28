<?php

namespace App\Tests\Functional;

use App\Entity\Edu\Teaching;
use App\Entity\ItpModule\StudentProgramWorkcenterActivityComment;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Las pantallas de confirmación de borrado se pueden abrir con un enlace (GET), pero solo borran
 * cuando la confirmación llega en el cuerpo de una petición POST: un enlace o una imagen en otra página
 * no deben poder borrar datos.
 */
class DeleteByLinkTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    public function testTeachingIsNotDeletedByALink(): void
    {
        $id = $this->em()->getRepository(Teaching::class)->findOneBy([])->getId();
        $url = '/centro/materia/asignacion/eliminar/' . $id;
        $this->login($this->getUser('alfa.admin'));

        $this->client->request('GET', $url . '?confirm=ok');
        self::assertResponseIsSuccessful();
        self::assertNotNull($this->em()->getRepository(Teaching::class)->find($id));

        $this->client->request('POST', $url, ['confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertNull($this->em()->getRepository(Teaching::class)->find($id));
    }

    public function testItpCommentIsNotDeletedByALink(): void
    {
        $id = $this->em()->getRepository(StudentProgramWorkcenterActivityComment::class)->findOneBy([])->getId();
        $url = '/formacion/valoracion/comentarios/eliminar/' . $id;
        // el autor del comentario de las fixtures es el profesor
        $this->login($this->getUser('alfa.profesor'));

        $this->client->request('GET', $url . '?confirm=ok');
        self::assertResponseIsSuccessful();
        self::assertNotNull($this->em()->getRepository(StudentProgramWorkcenterActivityComment::class)->find($id));

        $this->client->request('POST', $url, ['confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertNull($this->em()->getRepository(StudentProgramWorkcenterActivityComment::class)->find($id));
    }
}
