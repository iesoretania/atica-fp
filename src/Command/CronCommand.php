<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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

namespace App\Command;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\WLT\Agreement;
use App\Entity\WLT\Project;
use App\Entity\WLT\WorkDay;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\WLT\AgreementRepository;
use App\Repository\WLT\EducationalTutorAnsweredSurveyRepository;
use App\Repository\WLT\ProjectRepository;
use App\Repository\WLT\WLTTeacherRepository;
use App\Repository\WLT\WorkDayRepository;
use App\Service\MailerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

class CronCommand extends Command
{
    private $translator;
    private $mailerService;
    private $workDayRepository;
    private $academicYearRepository;
    private $agreementRepository;
    private $wltTeacherRepository;
    private $projectRepository;
    private $educationalTutorAnsweredSurveyRepository;

    public function __construct(
        TranslatorInterface $translator,
        MailerService $mailerService,
        WorkDayRepository $workDayRepository,
        AgreementRepository $agreementRepository,
        AcademicYearRepository $academicYearRepository,
        ProjectRepository $projectRepository,
        WLTTeacherRepository $wltTeacherRepository,
        EducationalTutorAnsweredSurveyRepository $educationalTutorAnsweredSurveyRepository
    ) {
        parent::__construct();
        $this->translator = $translator;
        $this->mailerService = $mailerService;
        $this->workDayRepository = $workDayRepository;
        $this->academicYearRepository = $academicYearRepository;
        $this->agreementRepository = $agreementRepository;
        $this->wltTeacherRepository = $wltTeacherRepository;
        $this->projectRepository = $projectRepository;
        $this->educationalTutorAnsweredSurveyRepository = $educationalTutorAnsweredSurveyRepository;
    }

    protected function configure()
    {
        $this
            ->setName('app:cron')
            ->setDescription('Execute cron tasks');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        $style->title($this->translator->trans('title.cron', [], 'cron'));

        $this->wltSendTrackingWarnings($output, $style);
        $this->wltSendSurveyWarnings($output, $style);
    }

    /**
     * @param OutputInterface $output
     * @param SymfonyStyle $style
     * @throws \Exception
     */
    protected function wltSendTrackingWarnings(OutputInterface $output, SymfonyStyle $style)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        // Inactividad del alumnado de FP dual
        $style->section($this->translator->trans('title.wlt.inactivity_warning', [], 'cron'));

        $academicYears = $this->academicYearRepository->findByDate($now);

        $limit = clone $now;
        $days = 7;
        $limit->modify('-' . $days . ' days');

        $table = new Table($output);

        $table
            ->setHeaders(explode('|', $this->translator->trans('table.wlt.inactivity_warning', [], 'cron')));

        $warning = [];
        /** @var AcademicYear $academicYear */
        foreach ($academicYears as $academicYear) {
            $agreements = $this->agreementRepository->findByAcademicYear($academicYear);
            /** @var Agreement $agreement */
            foreach ($agreements as $agreement) {
                $workDays = $this->workDayRepository->findUnfilledWorkDaysBeforeDateByAgreement($agreement, $limit);
                $count = is_array($workDays) || $workDays instanceof \Countable ? count($workDays) : 0;
                $table
                    ->addRow([
                        $academicYear->getOrganization()->getName(),
                        $academicYear->getDescription(),
                        $agreement->getStudentEnrollment(),
                        $agreement->getWorkcenter()->getCompany(),
                        $this->translator->trans(
                            $count > 0 ? 'table.wlt.inactivity_warning.status.warning' :
                                'table.wlt.inactivity_warning.status.ok',
                            ['%count%' => $count],
                            'cron'
                        )
                    ]);
                if ($count > 0) {
                    $warning[] = [$agreement, $workDays];
                }
            }
        }
        $table->render();

        if ($warning !== []) {
            $resultList = [];
            $style->text($this->translator->trans('message.sending_warnings', [], 'cron'));
            $style->progressStart(count($warning));
            foreach ($warning as $agreementData) {
                $workDays = $agreementData[1];
                $workDaysText = '';
                /** @var WorkDay $workDay */
                foreach ($workDays as $workDay) {
                    $day = $workDay->getDate()->setTimezone(new \DateTimeZone('UTC'));
                    $workDaysText .= '- ' .
                        $this->translator->trans(
                            'dow' . $day->format('w'), [], 'calendar'
                        ) . ', ' .
                        $day->format($this->translator->trans('format.date', [], 'general')) .
                        "\n";
                }
                /** @var Agreement $agreement */
                $agreement = $agreementData[0];
                $studentEnrollment = $agreement->getStudentEnrollment();
                $person = $studentEnrollment->getPerson();
                if (null === $person) {
                    $result = 'message.sending_warnings.no_user';
                } elseif ($person->getEmailAddress() === '') {
                    $result = 'message.sending_warnings.no_email_address';
                } else {
                    $params = [
                        '%name%' => $studentEnrollment->getPerson()->getFirstName(),
                        '%company%' => $agreement->getWorkcenter()->getCompany()->getName(),
                        '%days%' => $days,
                        '%organization%' => $studentEnrollment
                            ->getGroup()
                            ->getGrade()
                            ->getTraining()
                            ->getAcademicYear()
                            ->getOrganization()
                            ->getName(),
                        '%workdays%' => $workDaysText
                    ];

                    $this->mailerService->sendEmail(
                        [$person],
                        [
                            'id' => 'notification.inactivity_warning.subject',
                            'parameters' => $params
                        ],
                        [
                            'id' => 'notification.inactivity_warning.body',
                            'parameters' => $params
                        ],
                        'wlt_tracking'
                    );
                    $result = 'message.sending_warnings.sent';
                }

                $result =
                    $this->translator->trans(
                        $result,
                        [],
                        'cron'
                    );
                $style->progressAdvance();
                $resultList[] = $studentEnrollment . ': ' . $result;
            }
            $style->progressFinish();
            $output->writeln('');
            if ($resultList !== []) {
                $style->listing($resultList);
            }
        }

        $style->success($this->translator->trans('message.wlt_inactivity_warning.done', [], 'cron'));
    }

    /**
     * @param OutputInterface $output
     * @param SymfonyStyle $style
     * @throws \Exception
     */
    protected function wltSendSurveyWarnings(OutputInterface $output, SymfonyStyle $style)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        // Encuestas no contestadas a punto de cerrarse
        $style->section($this->translator->trans('title.wlt.unaswered_survey_warning', [], 'cron'));

        $academicYears = $this->academicYearRepository->findByDate($now);

        $limit = clone $now;
        $days = 7;
        $limit->modify('-' . $days . ' days');

        // Estudiantes
        $table = new Table($output);

        $table
            ->setHeaders(
                explode('|', $this->translator->trans('table.wlt.unaswered_survey_warning.student', [], 'cron'))
            );

        $warning = [];
        /** @var AcademicYear $academicYear */
        foreach ($academicYears as $academicYear) {
            $agreements = $this->agreementRepository->findByAcademicYear($academicYear);
            /** @var Agreement $agreement */
            foreach ($agreements as $agreement) {
                $referenceSurvey = $agreement
                    ->getProject()
                    ->getStudentSurvey();
                $count = 0;
                if ($referenceSurvey !== null) {
                    if ($agreement->getStudentSurvey()) {
                        $status = 'table.wlt.unaswered_survey_warning.status.ok';
                    } else {
                        $status = 'table.wlt.unaswered_survey_warning.status.on_time';
                        $closed = false;
                        if ($referenceSurvey->getStartTimestamp() && $referenceSurvey->getStartTimestamp() > $now) {
                            $closed = true;
                        }
                        if ($referenceSurvey->getEndTimestamp() && $referenceSurvey->getEndTimestamp() < $now) {
                            $closed = true;
                        }
                        if (!$closed) {
                            if ($referenceSurvey->getEndTimestamp() && $referenceSurvey->getEndTimestamp() > $limit) {
                                $status = 'table.wlt.unaswered_survey_warning.status.warning';
                                $count = (int) $now->diff($referenceSurvey->getEndTimestamp())->format('%R%a');
                            }
                        } else {
                            $status = 'table.wlt.unaswered_survey_warning.status.closed';
                        }
                    }
                    $table
                        ->addRow([
                            $agreement->getStudentEnrollment(),
                            $academicYear->getDescription(),
                            $agreement->getWorkcenter()->getCompany(),
                            $this->translator->trans(
                                $status,
                                ['%count%' => $count],
                                'cron'
                            )
                        ]);
                    if ($count > 0) {
                        $warning[] = [$agreement, $referenceSurvey->getEndTimestamp(), $count];
                    }
                }
            }
        }
        $table->render();

        if ($warning !== []) {
            $resultList = [];
            $style->text($this->translator->trans('message.sending_warnings', [], 'cron'));
            $style->progressStart(count($warning));
            foreach ($warning as $agreementData) {

                /** @var Agreement $agreement */
                $agreement = $agreementData[0];
                $workTutor = $agreement->getStudentEnrollment();
                $person = $workTutor->getPerson();
                if (null === $person) {
                    $result = 'message.sending_warnings.no_user';
                } elseif ($person->getEmailAddress() === '' || $person->getEmailAddress() === null) {
                    $result = 'message.sending_warnings.no_email_address';
                } else {
                    $params = [
                        '%name%' => $workTutor->getPerson()->getFirstName(),
                        '%company%' => $agreement->getWorkcenter()->getCompany()->getName(),
                        '%limit%' => $agreementData[1],
                        '%count%' => $agreementData[2],
                        '%organization%' => $workTutor
                            ->getGroup()
                            ->getGrade()
                            ->getTraining()
                            ->getAcademicYear()
                            ->getOrganization()
                            ->getName()
                    ];

                    $this->mailerService->sendEmail(
                        [$person],
                        [
                            'id' => 'notification.unanswered_survey_warning.subject',
                            'parameters' => $params
                        ],
                        [
                            'id' => 'notification.unanswered_survey_warning.body.student',
                            'parameters' => $params
                        ],
                        'wlt_survey'
                    );
                    $result = 'message.sending_warnings.sent';
                }

                $result =
                    $this->translator->trans(
                        $result,
                        [],
                        'cron'
                    );
                $style->progressAdvance();
                $resultList[] = $workTutor . ': ' . $result;
            }
            $style->progressFinish();
            $output->writeln('');
            if ($resultList !== []) {
                $style->listing($resultList);
            }
        }

        $style->success($this->translator->trans('message.unaswered_survey_warning.done', [], 'cron'));

        // Empresas
        $table = new Table($output);

        $table
            ->setHeaders(
                explode('|', $this->translator->trans('table.wlt.unaswered_survey_warning.company', [], 'cron'))
            );

        $warning = [];
        /** @var AcademicYear $academicYear */
        foreach ($academicYears as $academicYear) {
            $agreements = $this->agreementRepository->findByAcademicYear($academicYear);
            /** @var Agreement $agreement */
            foreach ($agreements as $agreement) {
                $referenceSurvey = $agreement
                    ->getProject()
                    ->getCompanySurvey();
                $count = 0;
                if ($referenceSurvey !== null) {
                    if ($agreement->getCompanySurvey()) {
                        $status = 'table.wlt.unaswered_survey_warning.status.ok';
                    } else {
                        $status = 'table.wlt.unaswered_survey_warning.status.on_time';
                        $closed = false;
                        if ($referenceSurvey->getStartTimestamp() && $referenceSurvey->getStartTimestamp() > $now) {
                            $closed = true;
                        }
                        if ($referenceSurvey->getEndTimestamp() && $referenceSurvey->getEndTimestamp() < $now) {
                            $closed = true;
                        }
                        if (!$closed) {
                            if ($referenceSurvey->getEndTimestamp() && $referenceSurvey->getEndTimestamp() > $limit) {
                                $status = 'table.wlt.unaswered_survey_warning.status.warning';
                                $count = (int) $now->diff($referenceSurvey->getEndTimestamp())->format('%R%a');
                            }
                        } else {
                            $status = 'table.wlt.unaswered_survey_warning.status.closed';
                        }
                    }
                    $table
                        ->addRow([
                            $agreement->getWorkTutor(),
                            $agreement->getWorkcenter()->getCompany(),
                            $academicYear->getDescription(),
                            $agreement->getStudentEnrollment(),
                            $this->translator->trans(
                                $status,
                                ['%count%' => $count],
                                'cron'
                            )
                        ]);
                    if ($count > 0) {
                        $warning[] = [$agreement, $referenceSurvey->getEndTimestamp(), $count];
                    }
                }
            }
        }
        $table->render();

        if ($warning !== []) {
            $resultList = [];
            $style->text($this->translator->trans('message.sending_warnings', [], 'cron'));
            $style->progressStart(count($warning));
            foreach ($warning as $agreementData) {

                /** @var Agreement $agreement */
                $agreement = $agreementData[0];
                $person = $agreement->getWorkTutor();
                if (null === $person) {
                    $result = 'message.sending_warnings.no_user';
                } elseif ($person->getEmailAddress() === '' || $person->getEmailAddress() === null) {
                    $result = 'message.sending_warnings.no_email_address';
                } else {
                    $params = [
                        '%name%' => $person->getFirstName(),
                        '%student%' => $agreement->getStudentEnrollment()->getPerson(),
                        '%company%' => $agreement->getWorkcenter()->getCompany()->getName(),
                        '%limit%' => $agreementData[1],
                        '%count%' => $agreementData[2],
                        '%organization%' => $workTutor
                            ->getGroup()
                            ->getGrade()
                            ->getTraining()
                            ->getAcademicYear()
                            ->getOrganization()
                            ->getName()
                    ];

                    $this->mailerService->sendEmail(
                        [$person],
                        [
                            'id' => 'notification.unanswered_survey_warning.subject',
                            'parameters' => $params
                        ],
                        [
                            'id' => 'notification.unanswered_survey_warning.body.company',
                            'parameters' => $params
                        ],
                        'wlt_survey'
                    );
                    $result = 'message.sending_warnings.sent';
                }

                $result =
                    $this->translator->trans(
                        $result,
                        [],
                        'cron'
                    );
                $style->progressAdvance();
                $resultList[] = $person . ': ' . $result;
            }
            $style->progressFinish();
            $output->writeln('');
            if ($resultList !== []) {
                $style->listing($resultList);
            }
        }

        $style->success($this->translator->trans('message.unaswered_survey_warning.done', [], 'cron'));

        // Tutores docentes del centro educativo
        $table = new Table($output);

        $table
            ->setHeaders(
                explode(
                    '|',
                    $this->translator->trans('table.wlt.unaswered_survey_warning.organization', [], 'cron')
                )
            );

        $warning = [];

        $projects = $this->projectRepository->findAll();

        /** @var Project $project */
        foreach ($projects as $project) {
            $referenceSurvey = $project->getEducationalTutorSurvey();
            /** @var Teacher $teacher */
            foreach ($this->wltTeacherRepository->findByProject($project) as $teacher) {
                $count = 0;
                if ($referenceSurvey !== null) {
                    if ($this->educationalTutorAnsweredSurveyRepository->findOneByProjectAndTeacher($project, $teacher)) {
                        $status = 'table.wlt.unaswered_survey_warning.status.ok';
                    } else {
                        $status = 'table.wlt.unaswered_survey_warning.status.on_time';
                        $closed = false;
                        if ($referenceSurvey->getStartTimestamp() && $referenceSurvey->getStartTimestamp() > $now) {
                            $closed = true;
                        }
                        if ($referenceSurvey->getEndTimestamp() && $referenceSurvey->getEndTimestamp() < $now) {
                            $closed = true;
                        }
                        if (!$closed) {
                            if ($referenceSurvey->getEndTimestamp() && $referenceSurvey->getEndTimestamp() > $limit) {
                                $status = 'table.wlt.unaswered_survey_warning.status.warning';
                                $count = (int) $now->diff($referenceSurvey->getEndTimestamp())->format('%R%a');
                            }
                        } else {
                            $status = 'table.wlt.unaswered_survey_warning.status.closed';
                        }
                    }
                    $table
                        ->addRow([
                            (string) $teacher,
                            $academicYear->getDescription(),
                            $this->translator->trans(
                                $status,
                                ['%count%' => $count],
                                'cron'
                            )
                        ]);
                    if ($count > 0) {
                        $warning[] = [$teacher, $referenceSurvey->getEndTimestamp(), $count];
                    }
                }
            }
        }
        $table->render();

        if ($warning !== []) {
            $resultList = [];
            $style->text($this->translator->trans('message.sending_warnings', [], 'cron'));
            $style->progressStart(count($warning));
            foreach ($warning as $personData) {
                $teacher = $personData[0];
                $person = $teacher->getPerson();
                if (null === $person) {
                    $result = 'message.sending_warnings.no_user';
                } elseif ($person->getEmailAddress() === '' || $person->getEmailAddress() === null) {
                    $result = 'message.sending_warnings.no_email_address';
                } else {
                    $params = [
                        '%name%' => $person->getFirstName(),
                        '%academic_year%' => $teacher->getAcademicYear()->getDescription(),
                        '%limit%' => $personData[1],
                        '%count%' => $personData[2],
                        '%organization%' => $teacher->getAcademicYear()->getOrganization()->getName()
                    ];

                    $this->mailerService->sendEmail(
                        [$person],
                        [
                            'id' => 'notification.unanswered_survey_warning.subject',
                            'parameters' => $params
                        ],
                        [
                            'id' => 'notification.unanswered_survey_warning.body.organization',
                            'parameters' => $params
                        ],
                        'wlt_survey'
                    );
                    $result = 'message.sending_warnings.sent';
                }

                $result =
                    $this->translator->trans(
                        $result,
                        [],
                        'cron'
                    );
                $style->progressAdvance();
                $resultList[] = $person . ': ' . $result;
            }
            $style->progressFinish();
            $output->writeln('');
            if ($resultList !== []) {
                $style->listing($resultList);
            }
        }

        $style->success($this->translator->trans('message.unaswered_survey_warning.done', [], 'cron'));
    }
}
