<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\AnsweredSurvey;
use App\Entity\Survey;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Encuestas del centro. */
#[AsLiveComponent('Organization:SurveyList', template: 'components/Organization/SurveyList.html.twig')]
final class SurveyList extends AbstractOrganizationList
{
    protected function sortableColumns(): array
    {
        return ['title' => 's.title', 'start' => 's.startTimestamp', 'end' => 's.endTimestamp'];
    }

    /** @var array<int, int> respuestas de cada encuesta de la página, por identificador */
    private array $answerCounts = [];

    /** Respuestas de todas las encuestas de la página en una sola consulta (un recuento por fila sería un N+1). */
    protected function preload(array $items): void
    {
        $rows = $this->newQueryBuilder()
            ->select('IDENTITY(a.survey) AS survey_id', 'COUNT(a.id) AS total')
            ->from(AnsweredSurvey::class, 'a')
            ->where('a.survey IN (:surveys)')
            ->setParameter('surveys', $items)
            ->groupBy('a.survey')
            ->getQuery()
            ->getArrayResult();
        foreach ($rows as $row) {
            $this->answerCounts[(int) $row['survey_id']] = (int) $row['total'];
        }
    }

    public function answerCount(Survey $survey): int
    {
        return $this->answerCounts[$survey->getId()] ?? 0;
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('s')
            ->from(Survey::class, 's')
            ->orderBy('s.title');

        if ($this->q !== '') {
            $queryBuilder->where('s.title LIKE :tq')->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('s.organization = :organization')
            ->setParameter('organization', $this->organization());
    }
}
