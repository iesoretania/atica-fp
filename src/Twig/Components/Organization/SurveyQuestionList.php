<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Survey;
use App\Entity\SurveyQuestion;
use App\Security\SurveyVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Preguntas de una encuesta. */
#[AsLiveComponent('Organization:SurveyQuestionList', template: 'components/Organization/SurveyQuestionList.html.twig')]
final class SurveyQuestionList extends AbstractListComponent
{
    #[LiveProp]
    public Survey $survey;

    protected function authorize(): void
    {
        if ($this->survey->getOrganization() !== $this->organization()) {
            throw new AccessDeniedException();
        }
        $this->denyUnlessGranted(SurveyVoter::MANAGE, $this->survey);
    }

    /** Con respuestas recogidas no se pueden añadir, borrar ni reordenar preguntas. */
    public function isLocked(): bool
    {
        return $this->survey->getAnswers()->count() > 0;
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('sq')
            ->from(SurveyQuestion::class, 'sq')
            ->orderBy('sq.orderNr');

        if ($this->q !== '') {
            $queryBuilder->where('sq.description LIKE :tq')->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('sq.survey = :survey')
            ->setParameter('survey', $this->survey);
    }
}
