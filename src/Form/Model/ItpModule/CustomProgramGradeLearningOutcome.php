<?php

namespace App\Form\Model\ItpModule;

use App\Entity\ItpModule\ProgramGradeLearningOutcome;

class CustomProgramGradeLearningOutcome
{
    private int $selected = 0;
    private ProgramGradeLearningOutcome $programGradeLearningOutcome;

    public function getSelected(): int
    {
        return $this->selected;
    }

    public function setSelected(int $selected): CustomProgramGradeLearningOutcome
    {
        $this->selected = $selected;
        return $this;
    }

    public function getProgramGradeLearningOutcome(): ProgramGradeLearningOutcome
    {
        return $this->programGradeLearningOutcome;
    }

    public function setProgramGradeLearningOutcome(ProgramGradeLearningOutcome $programGradeLearningOutcome): CustomProgramGradeLearningOutcome
    {
        $this->programGradeLearningOutcome = $programGradeLearningOutcome;
        return $this;
    }
}
