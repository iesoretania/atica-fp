<?php
/*
  Copyright (C) 2018: Luis Ramón López López

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see [http://www.gnu.org/licenses/].
*/

namespace AppBundle\Entity\WLT;

use AppBundle\Entity\Edu\LearningOutcome;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WLT\ActivityRealizationRepository")
 * @ORM\Table(name="wlt_activity_realization")
 */
class ActivityRealization
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Activity")
     * @ORM\JoinColumn(nullable=false)
     * @var Activity
     */
    private $activity;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $code;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    private $description;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Edu\LearningOutcome")
     * @ORM\JoinTable(name="wlt_activity_realization_learning_outcome")
     * @var LearningOutcome[]
     */
    private $learningOutcomes;

    public function __construct()
    {
        $this->learningOutcomes = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @param Activity $activity
     * @return ActivityRealization
     */
    public function setActivity(Activity $activity)
    {
        $this->activity = $activity;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return ActivityRealization
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return ActivityRealization
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return LearningOutcome[]
     */
    public function getLearningOutcomes()
    {
        return $this->learningOutcomes;
    }

    /**
     * @param LearningOutcome[] $learningOutcomes
     * @return ActivityRealization
     */
    public function setLearningOutcomes($learningOutcomes)
    {
        $this->learningOutcomes = $learningOutcomes;
        return $this;
    }

    /**
     * @return array
     */
    public function getSubjectLearningOutcomes()
    {
        $data = [];

        foreach ($this->getLearningOutcomes() as $learningOutcome) {
            $subject = $learningOutcome->getSubject();
            $data[$subject->getCode() ?: $subject->getName() ][] = $learningOutcome;
        }

        return $data;
    }
}
