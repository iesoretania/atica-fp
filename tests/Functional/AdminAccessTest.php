<?php

namespace App\Tests\Functional;

class AdminAccessTest extends AppWebTestCase
{
    public function testRegularUserCannotListUsers(): void
    {
        $this->login($this->getRegularUser());
        $this->client->request('GET', '/admin/usuarios/listar');

        self::assertResponseStatusCodeSame(403);
    }

    public function testRegularUserCannotOpenUserForm(): void
    {
        $this->login($this->getRegularUser());
        $this->client->request('GET', '/admin/usuarios/nuevo');

        self::assertResponseStatusCodeSame(403);
    }

    public function testRegularUserCannotDeleteUsers(): void
    {
        $this->login($this->getRegularUser());
        $this->client->request('POST', '/admin/usuarios/eliminar', ['items' => [$this->getAdmin()->getId()]]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanListUsers(): void
    {
        $this->login($this->getAdmin());
        $this->client->request('GET', '/admin/usuarios/listar');

        self::assertResponseIsSuccessful();
    }
}
