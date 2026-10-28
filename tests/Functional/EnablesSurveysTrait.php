<?php

namespace App\Tests\Functional;

use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\Organization;
use App\Entity\Survey;
use App\Entity\SurveyQuestion;
use App\Entity\WltModule\Agreement;
use App\Entity\WptModule\AgreementEnrollment;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Las fixtures no incluyen encuestas: este trait crea las del alumnado y de empresa de FCT, FP dual y FFE.
 * Participantes:
 *  - FCT: alumno1 y alumno2 (acuerdo 1)
 *  - FP dual: alumno5
 *  - FFE: alumno7
 */
trait EnablesSurveysTrait
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function createSurvey(EntityManagerInterface $em, string $title): Survey
    {
        $survey = (new Survey())
            ->setOrganization($em->getRepository(Organization::class)->findOneBy(['code' => DemoData::ORG_A_CODE]))
            ->setTitle($title)
            ->setStartTimestamp(new \DateTime('-1 year'))
            ->setEndTimestamp(new \DateTime('+1 year'));
        $em->persist($survey);
        $question = (new SurveyQuestion())
            ->setSurvey($survey)->setDescription('Valoración general')->setType(SurveyQuestion::RANGE_1_5)
            ->setMandatory(false)->setOrderNr(1);
        $em->persist($question);

        return $survey;
    }

    /**
     * Asigna encuestas de alumnado y de empresa a la fase de FCT, al proyecto de FP dual y al programa de FFE
     */
    private function enableSurveys(): void
    {
        // los identificadores se obtienen antes de modificar nada: los helpers vacían el gestor de entidades
        $enrollmentId = $this->wptEnrollment('alfa.alumno1')->getId();
        $agreementId = $this->wltAgreement()->getId();
        $stayId = $this->itpStay()->getId();

        $em = $this->em();
        $shift = $em->find(AgreementEnrollment::class, $enrollmentId)->getAgreement()->getShift();
        $shift->setStudentSurvey($this->createSurvey($em, 'FCT alumnado'))->setCompanySurvey($this->createSurvey($em, 'FCT empresa'));

        $agreement = $em->find(Agreement::class, $agreementId);
        $agreement->getProject()->setStudentSurvey($this->createSurvey($em, 'Dual alumnado'))
            ->setCompanySurvey($this->createSurvey($em, 'Dual empresa'));

        $stay = $em->find(StudentProgramWorkcenter::class, $stayId);
        $stay->getStudentProgram()->getProgramGroup()->getProgramGrade()->getTrainingProgram()
            ->setStudentSurvey($this->createSurvey($em, 'FFE alumnado'))->setCompanySurvey($this->createSurvey($em, 'FFE empresa'));

        $em->flush();
        $em->clear();
    }

    private function wptEnrollment(string $username): AgreementEnrollment
    {
        return $this->em()->getRepository(AgreementEnrollment::class)->createQueryBuilder('ae')
            ->join('ae.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->setParameter('username', $username)
            ->getQuery()->getSingleResult();
    }

    private function wltAgreement(): Agreement
    {
        return $this->em()->getRepository(Agreement::class)->createQueryBuilder('a')
            ->join('a.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->setParameter('username', 'alfa.alumno5')
            ->getQuery()->getSingleResult();
    }

    private function itpStay(): StudentProgramWorkcenter
    {
        return $this->em()->getRepository(StudentProgramWorkcenter::class)->createQueryBuilder('s')
            ->join('s.studentProgram', 'sp')->join('sp.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->setParameter('username', 'alfa.alumno7')
            ->getQuery()->getSingleResult();
    }
}
