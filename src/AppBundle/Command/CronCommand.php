<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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

namespace AppBundle\Command;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\WLT\Agreement;
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Repository\WLT\AgreementRepository;
use AppBundle\Repository\WLT\WorkDayRepository;
use AppBundle\Service\MailerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Translation\TranslatorInterface;

class CronCommand extends Command
{
    private $translator;
    private $mailerService;
    private $workDayRepository;
    private $academicYearRepository;
    private $agreementRepository;

    public function __construct(
        TranslatorInterface $translator,
        MailerService $mailerService,
        WorkDayRepository $workDayRepository,
        AgreementRepository $agreementRepository,
        AcademicYearRepository $academicYearRepository
    ) {
        parent::__construct();
        $this->translator = $translator;
        $this->mailerService = $mailerService;
        $this->workDayRepository = $workDayRepository;
        $this->academicYearRepository = $academicYearRepository;
        $this->agreementRepository = $agreementRepository;
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

        $this->wltSendWarnings($output, $style);
    }

    /**
     * @param OutputInterface $output
     * @param SymfonyStyle $style
     * @throws \Exception
     */
    protected function wltSendWarnings(OutputInterface $output, SymfonyStyle $style)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        // Inactividad del alumnado de FP dual
        $style->section($this->translator->trans('title.wlt.inactivity_warning', [], 'cron'));

        $academicYears = $this->academicYearRepository->findByDate($now);

        $limit = clone $now;
        $limit->modify('-1 week');

        $table = new Table($output);

        $table
            ->setHeaders(explode('|', $this->translator->trans('table.wlt.inactivity_warning', [], 'cron')));

        $warning = [];
        /** @var AcademicYear $academicYear */
        foreach ($academicYears as $academicYear) {
            $agreements = $this->agreementRepository->findByAcademicYear($academicYear);
            /** @var Agreement $agreement */
            foreach ($agreements as $agreement) {
                $count = $this->workDayRepository->countUnfilledWorkDaysBeforeDateByAgreement($agreement, $limit);
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
                    $warning[] = [$agreement, $count];
                }
            }
        }
        $table->render();

        if (count($warning) > 0) {
            $resultList = [];
            $style->text($this->translator->trans('message.wlt_inactivity_warning.sending_warnings', [], 'cron'));
            $style->progressStart(count($warning));
            foreach ($warning as $agreementData) {
                /** @var Agreement $agreement */
                $agreement = $agreementData[0];
                $studentEnrollment = $agreement->getStudentEnrollment();
                $person = $studentEnrollment->getPerson();
                if (null === $person->getUser()) {
                    $result = 'message.wlt_inactivity_warning.sending_warnings.no_user';
                } elseif (!$person->getUser()->getEmailAddress()) {
                    $result = 'message.wlt_inactivity_warning.sending_warnings.no_email_address';
                } else {
                    $params = [
                        '%name%' => (string) $studentEnrollment->getPerson(),
                        '%count%' => $agreementData[1],
                        '%company%' => $agreement->getWorkcenter()->getCompany()->getName(),
                        '%organization%' => $studentEnrollment
                            ->getGroup()
                            ->getGrade()
                            ->getTraining()
                            ->getAcademicYear()
                            ->getOrganization()
                            ->getName()
                    ];

                    $this->mailerService->sendEmail(
                        [$person->getUser()],
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
                    $result = 'message.wlt_inactivity_warning.sending_warnings.sent';
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
            if (count($resultList) > 0) {
                $style->listing($resultList);
            }
        }

        $style->success($this->translator->trans('message.wlt_inactivity_warning.done', [], 'cron'));
    }
}
