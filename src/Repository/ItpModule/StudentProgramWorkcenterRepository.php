<?php

namespace App\Repository\ItpModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\StudentProgram;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\Person;
use App\Repository\Edu\GroupRepository;
use App\Repository\Edu\TeacherRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentProgram>
 */
class StudentProgramWorkcenterRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry                         $registry,
        private readonly WorkDayRepository      $workDayRepository,
        private readonly ProgramGroupRepository $programGroupRepository, private readonly TeacherRepository $teacherRepository, private readonly GroupRepository $groupRepository
    )
    {
        parent::__construct($registry, StudentProgramWorkcenter::class);
    }

    public function deleteFromStudentProgramList(array $items): void
    {
        $workDays = $this->workDayRepository->findByStudentProgramWorkcenters($items);
        $this->workDayRepository->deleteFromList($workDays);
        $this->createQueryBuilder('spw')
            ->delete()
            ->where('spw.studentProgram IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    public function createByStudentProgramQueryBuilder(StudentProgram $studentProgram, ?string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('spw')
            ->addSelect('spw', 'c', 'w')
            ->join('spw.workcenter', 'w')
            ->join('w.company', 'c')
            ->where('spw.studentProgram = :studentProgram')
            ->setParameter('studentProgram', $studentProgram)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('w.name', 'ASC');

        if ($q) {
            $qb
                ->andWhere('w.name LIKE :tq OR c.name LIKE :tq')
                ->setParameter('tq', "%" . $q . "%");
        }

        return $qb;
    }

    public function persist(StudentProgramWorkcenter $studentProgramWorkcenter): void
    {
        $this->getEntityManager()->persist($studentProgramWorkcenter);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    final public function findAllInListByIdAndStudentProgram(array $items, StudentProgram $studentProgram): array
    {
        return $this->createQueryBuilder('spw')
            ->addSelect('spw', 'c', 'w', 'p', 'g', 'sp', 'se')
            ->join('spw.studentProgram', 'sp')
            ->join('sp.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('spw.workcenter', 'w')
            ->join('w.company', 'c')
            ->where('spw.id IN (:items)')
            ->andWhere('spw.studentProgram = :studentProgram')
            ->setParameter('items', $items)
            ->setParameter('studentProgram', $studentProgram)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('w.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    final public function deleteFromList(array $selectedItems): void
    {
        $this->createQueryBuilder('spw')
            ->delete()
            ->where('spw.id IN (:selectedItems)')
            ->setParameter('selectedItems', $selectedItems)
            ->getQuery()
            ->execute();
    }

    final public function updateDates(StudentProgramWorkcenter $studentProgramWorkcenter): void
    {
        static $timezone = new \DateTimeZone('UTC');
        $stats = $this->workDayRepository->getStudentProgramWorkcenterStats($studentProgramWorkcenter);
        if (isset($stats['endDate'], $stats['startDate'])) {
            $studentProgramWorkcenter->setStartDate(new \DateTimeImmutable($stats['startDate'], $timezone));
            $studentProgramWorkcenter->setEndDate(new \DateTimeImmutable($stats['endDate'], $timezone));
        }
    }

    public function createTrackingQueryBuilder(
        AcademicYear $academicYear,
        Person $person,
        bool $isManager,
        ?string $q): QueryBuilder
    {
        $teacher = $this->teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);
        if (!$isManager && $teacher instanceof Teacher) {
            // Grupos donde se es tutor dual
            $programGroups = $this->programGroupRepository->findByManager($teacher);
            $groups = array_map(fn($group) => $group->getGroup(), $programGroups);

            // Si se es jefe de departamento
            $departmentGroups = $this->groupRepository->findByDepartmentHead($teacher);
            foreach ($departmentGroups as $group) {
                if (!in_array($group, $groups)) {
                    $groups[] = $group;
                }
            }

            // Si son tutores del grupo
            $tutorGroups = $this->groupRepository->findByTutor($teacher);
            foreach ($tutorGroups as $group) {
                if (!in_array($group, $groups)) {
                    $groups[] = $group;
                }
            }
        } else {
            $groups = [];
        }
        $qb = $this->createQueryBuilder('spw')
            ->addSelect('spw', 'c', 'w', 'p', 'g', 'gr', 't', 'sp', 'se', 'et', 'etp', 'wt')
            ->join('spw.studentProgram', 'sp')
            ->join('spw.educationalTutor', 'et')
            ->join('et.person', 'etp')
            ->leftJoin('spw.additionalEducationalTutor', 'aet')
            ->leftJoin('aet.person', 'aetp')
            ->join('spw.workTutor', 'wt')
            ->leftJoin('spw.additionalWorkTutor', 'awt')
            ->join('sp.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('spw.workcenter', 'w')
            ->join('w.company', 'c');

        if ($isManager) {
            $qb->
                where('t.academicYear = :academicYear')
                ->setParameter('academicYear', $academicYear);
        } else {
            $qb
                ->where('t.academicYear = :academicYear AND (p = :person OR etp = :person OR wt = :person OR aetp = :person OR awt = :person'
                    . (count($groups) ? ' OR g IN (:groups)' : '')
                    . ')')
                ->setParameter('academicYear', $academicYear)
                ->setParameter('person', $person);
            if (count($groups) > 0) {
                $qb->setParameter('groups', $groups);
            }
        }

        $qb
            ->orderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->addOrderBy('w.name', 'ASC');

        if ($q) {
            $qb
                ->andWhere('p.firstName LIKE :tq OR p.lastName LIKE :tq OR w.name LIKE :tq OR c.name LIKE :tq OR wt.firstName LIKE :tq OR wt.lastName LIKE :tq OR etp.firstName LIKE :tq OR etp.lastName LIKE :tq OR g.name LIKE :tq')
                ->setParameter('tq', "%" . $q . "%");
        }
        return $qb;
    }

    public function findByStudentAndAcademicYear(Person $user, ?AcademicYear $academicYear): array
    {
        if (!$academicYear instanceof AcademicYear) {
            return [];
        }
        return $this->createQueryBuilder('spw')
            ->join('spw.studentProgram', 'sp')
            ->join('sp.studentEnrollment', 'se')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->where('se.person = :person')
            ->andWhere('t.academicYear = :academicYear')
            ->setParameter('person', $user)
            ->setParameter('academicYear', $academicYear)
            ->getQuery()
            ->getResult();
    }

    public function findByEducationalTutorOrAdditionalEducationalTutor(Teacher $teacher): array
    {
        return $this->createQueryBuilder('spw')
            ->join('spw.educationalTutor', 'et')
            ->leftJoin('spw.additionalEducationalTutor', 'aet')
            ->where('et = :teacher OR aet = :teacher')
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getResult();
    }

    public function findByWorkTutorOrAdditionalWorkTutorAndAcademicYear(Person $person, AcademicYear $academicYear): array
    {
        return $this->createQueryBuilder('spw')
            ->join('spw.educationalTutor', 'et')
            ->join('spw.workTutor', 'wt')
            ->leftJoin('spw.additionalWorkTutor', 'awt')
            ->where('wt = :person OR awt = :person')
            ->andWhere('et.academicYear = :academic_year')
            ->setParameter('person', $person)
            ->setParameter('academic_year', $academicYear)
            ->getQuery()
            ->getResult();
    }

    public function createFindByProgramGradeAndFilterQueryBuilder(ProgramGrade $programGrade, ?string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('spw')
            ->addSelect('spw', 'c', 'w', 'p', 'pg', 'g', 'et', 'etp', 'wt', 'aet', 'aetp', 'sp', 'se')
            ->join('spw.workcenter', 'w')
            ->join('w.company', 'c')
            ->join('spw.educationalTutor', 'et')
            ->join('et.person', 'etp')
            ->leftJoin('spw.additionalEducationalTutor', 'aet')
            ->leftJoin('aet.person', 'aetp')
            ->join('spw.workTutor', 'wt')
            ->leftJoin('spw.additionalWorkTutor', 'awt')
            ->join('spw.studentProgram', 'sp')
            ->join('sp.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('sp.programGroup', 'pg')
            ->join('pg.group', 'g')
            ->where('pg.programGrade = :program_grade')
            ->setParameter('program_grade', $programGrade)
            ->addOrderBy('g.name', 'ASC')
            ->addOrderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->addOrderBy('w.name', 'ASC');

        if ($q) {
            $qb
                ->andWhere('p.firstName LIKE :tq OR p.lastName LIKE :tq OR g.name LIKE :tq OR c.name LIKE :tq OR w.name LIKE :tq OR etp.firstName LIKE :tq OR etp.lastName LIKE :tq OR wt.firstName LIKE :tq OR wt.lastName LIKE :tq OR aetp.firstName LIKE :tq OR aetp.lastName LIKE :tq')
                ->setParameter('tq', "%" . $q . "%");
        }

        return $qb;
    }
}
