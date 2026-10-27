<?php

namespace App\Tests\Unit\Security;

use App\Security\CachedVoter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CachedVoterTest extends VoterTestCase
{
    /**
     * Voter de prueba que cuenta cuántas veces se evalúa realmente un permiso y
     * concede el acceso solo al usuario indicado.
     */
    private function voter(ArrayAdapter $cache, object $allowedUser, int &$evaluations): CachedVoter
    {
        return new class ($cache, $allowedUser, $evaluations) extends CachedVoter {
            public function __construct(ArrayAdapter $cache, private readonly object $allowedUser, private int &$evaluations)
            {
                parent::__construct($cache);
            }

            protected function supports(string $attribute, mixed $subject): bool
            {
                return $attribute === 'TEST_MANAGE' && $subject instanceof \stdClass;
            }

            protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
            {
                $this->evaluations++;
                return $token->getUser() === $this->allowedUser;
            }
        };
    }

    public function testAbstainsWhenAttributeIsNotSupportedAndDoesNotEvaluate(): void
    {
        $evaluations = 0;
        $user = $this->person();
        $voter = $this->voter($this->cache(), $user, $evaluations);

        self::assertSame(0, $voter->vote($this->token($user), new \stdClass(), ['OTRO']));
        self::assertSame(0, $evaluations);
    }

    public function testRepeatedVotesForTheSameSubjectAreEvaluatedOnlyOnce(): void
    {
        $evaluations = 0;
        $user = $this->person();
        $voter = $this->voter($this->cache(), $user, $evaluations);
        $subject = new \stdClass();

        self::assertSame(self::granted(), $voter->vote($this->token($user), $subject, ['TEST_MANAGE']));
        self::assertSame(self::granted(), $voter->vote($this->token($user), $subject, ['TEST_MANAGE']));
        self::assertSame(1, $evaluations);
    }

    public function testDifferentSubjectsAreCachedSeparately(): void
    {
        $evaluations = 0;
        $user = $this->person();
        $voter = $this->voter($this->cache(), $user, $evaluations);

        $voter->vote($this->token($user), new \stdClass(), ['TEST_MANAGE']);
        $second = new \stdClass();
        $voter->vote($this->token($user), $second, ['TEST_MANAGE']);

        self::assertSame(2, $evaluations);
    }

    public function testDeniedVotesAreAlsoCachedAndReturnedAsDenied(): void
    {
        $evaluations = 0;
        $voter = $this->voter($this->cache(), $this->person(), $evaluations);
        $subject = new \stdClass();
        $other = $this->token($this->person());

        self::assertSame(self::denied(), $voter->vote($other, $subject, ['TEST_MANAGE']));
        self::assertSame(self::denied(), $voter->vote($other, $subject, ['TEST_MANAGE']));
        self::assertSame(1, $evaluations);
    }

    public function testCachedVoteMustNotBeSharedBetweenDifferentUsers(): void
    {
        $evaluations = 0;
        $allowed = $this->person();
        $voter = $this->voter($this->cache(), $allowed, $evaluations);
        $subject = new \stdClass();

        self::assertSame(self::granted(), $voter->vote($this->token($allowed), $subject, ['TEST_MANAGE']));
        self::assertSame(
            self::denied(),
            $voter->vote($this->token($this->person()), $subject, ['TEST_MANAGE']),
            'Un voto concedido a un usuario no debe reutilizarse para otro'
        );
    }
}
