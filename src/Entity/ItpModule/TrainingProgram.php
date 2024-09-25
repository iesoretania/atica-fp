<?php

namespace App\Entity\ItpModule;

use App\Entity\Edu\PerformanceScale;
use App\Entity\Edu\ReportTemplate;
use App\Entity\Edu\Training;
use App\Entity\Survey;
use App\Repository\ItpModule\TrainingProgramRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrainingProgramRepository::class)]
#[ORM\Table(name: 'itp_training_program')]
class TrainingProgram
{
    public const MODE_GENERAL = 1;
    public const MODE_INTENSIVE = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(unique: true, nullable: false)]
    private ?Training $training = null;

    #[ORM\Column(nullable: true)]
    private ?int $targetHours = null;

    #[ORM\Column]
    private ?int $defaultModality = self::MODE_GENERAL;

    /**
     * @var Collection<int, Activity>
     */
    #[ORM\OneToMany(targetEntity: Activity::class, mappedBy: 'trainingProgram', orphanRemoval: true)]
    private Collection $activities;

    /**
     * @var Collection<int, SpecificTraining>
     */
    #[ORM\OneToMany(targetEntity: SpecificTraining::class, mappedBy: 'trainingProgram', orphanRemoval: true)]
    private Collection $specificTrainings;

    /**
     * @var Collection<int, TrainingProgramGroup>
     */
    #[ORM\OneToMany(targetEntity: TrainingProgramGroup::class, mappedBy: 'trainingProgram', orphanRemoval: true)]
    private Collection $trainingProgramGroups;

    /**
     * @var Collection<int, ProgramGrade>
     */
    #[ORM\OneToMany(targetEntity: ProgramGrade::class, mappedBy: 'trainingProgram', orphanRemoval: true)]
    private Collection $trainingProgramGrades;

    /**
     * @var Collection<int, ProgramGrade>
     */
    #[ORM\OneToMany(targetEntity: CompanyProgram::class, mappedBy: 'trainingProgram', orphanRemoval: true)]
    private Collection $companyPrograms;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?PerformanceScale $performanceScale = null;
    #[ORM\ManyToOne(targetEntity: Survey::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Survey $studentSurvey = null;

    #[ORM\ManyToOne(targetEntity: Survey::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Survey $companySurvey = null;

    #[ORM\ManyToOne(targetEntity: Survey::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Survey $educationalTutorSurvey = null;

    #[ORM\ManyToOne(targetEntity: ReportTemplate::class)]
    private ?ReportTemplate $attendanceReportTemplate = null;

    #[ORM\ManyToOne(targetEntity: ReportTemplate::class)]
    private ?ReportTemplate $finalReportTemplate = null;

    #[ORM\ManyToOne(targetEntity: ReportTemplate::class)]
    private ?ReportTemplate $weeklyActivityReportTemplate = null;

    #[ORM\ManyToOne(targetEntity: ReportTemplate::class)]
    private ?ReportTemplate $activitySummaryReportTemplate = null;

    #[ORM\Column]
    private ?bool $locked = false;

    public function __construct()
    {
        $this->activities = new ArrayCollection();
        $this->specificTrainings = new ArrayCollection();
        $this->trainingProgramGroups = new ArrayCollection();
        $this->trainingProgramGrades = new ArrayCollection();
        $this->companyPrograms = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTraining(): ?Training
    {
        return $this->training;
    }

    public function setTraining(Training $training): static
    {
        $this->training = $training;

        return $this;
    }

    public function getTargetHours(): ?int
    {
        return $this->targetHours;
    }

    public function setTargetHours(?int $targetHours): static
    {
        $this->targetHours = $targetHours;

        return $this;
    }

    public function getDefaultModality(): ?int
    {
        return $this->defaultModality;
    }

    public function setDefaultModality(int $defaultModality): static
    {
        $this->defaultModality = $defaultModality;

        return $this;
    }

    /**
     * @return Collection<int, Activity>
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    /**
     * @return Collection<int, SpecificTraining>
     */
    public function getSpecificTrainings(): Collection
    {
        return $this->specificTrainings;
    }

    /**
     * @return Collection<int, TrainingProgramGroup>
     */
    public function getTrainingProgramGroups(): Collection
    {
        return $this->trainingProgramGroups;
    }

    /**
     * @return Collection<int, ProgramGrade>
     */
    public function getTrainingProgramGrades(): Collection
    {
        return $this->trainingProgramGrades;
    }

    /**
     * @return Collection<int, CompanyProgram>
     */
    public function getCompanyPrograms(): Collection
    {
        return $this->companyPrograms;
    }

    public function getPerformanceScale(): ?PerformanceScale
    {
        return $this->performanceScale;
    }

    public function setPerformanceScale(?PerformanceScale $performanceScale): static
    {
        $this->performanceScale = $performanceScale;

        return $this;
    }

    public function isLocked(): ?bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): static
    {
        $this->locked = $locked;

        return $this;
    }

    public function getStudentSurvey(): ?Survey
    {
        return $this->studentSurvey;
    }

    public function setStudentSurvey(?Survey $studentSurvey): static
    {
        $this->studentSurvey = $studentSurvey;
        return $this;
    }

    public function getCompanySurvey(): ?Survey
    {
        return $this->companySurvey;
    }

    public function setCompanySurvey(?Survey $companySurvey): static
    {
        $this->companySurvey = $companySurvey;
        return $this;
    }

    public function getEducationalTutorSurvey(): ?Survey
    {
        return $this->educationalTutorSurvey;
    }

    public function setEducationalTutorSurvey(?Survey $educationalTutorSurvey): static
    {
        $this->educationalTutorSurvey = $educationalTutorSurvey;
        return $this;
    }

    public function getAttendanceReportTemplate(): ?ReportTemplate
    {
        return $this->attendanceReportTemplate;
    }

    public function setAttendanceReportTemplate(?ReportTemplate $attendanceReportTemplate): static
    {
        $this->attendanceReportTemplate = $attendanceReportTemplate;
        return $this;
    }

    public function getWeeklyActivityReportTemplate(): ?ReportTemplate
    {
        return $this->weeklyActivityReportTemplate;
    }

    public function setWeeklyActivityReportTemplate(?ReportTemplate $weeklyActivityReportTemplate): static
    {
        $this->weeklyActivityReportTemplate = $weeklyActivityReportTemplate;
        return $this;
    }

    public function getFinalReportTemplate(): ?ReportTemplate
    {
        return $this->finalReportTemplate;
    }

    public function setFinalReportTemplate(?ReportTemplate $finalReportTemplate): static
    {
        $this->finalReportTemplate = $finalReportTemplate;
        return $this;
    }

    public function getActivitySummaryReportTemplate(): ?ReportTemplate
    {
        return $this->activitySummaryReportTemplate;
    }

    public function setActivitySummaryReportTemplate(?ReportTemplate $activitySummaryReportTemplate): static
    {
        $this->activitySummaryReportTemplate = $activitySummaryReportTemplate;
        return $this;
    }
}
