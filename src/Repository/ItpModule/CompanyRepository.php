<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\CompanyProgram;
use App\Entity\ItpModule\ProgramGrade;
use App\Repository\CompanyRepository as BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

class CompanyRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
    }

    public function findAllButInProgramGrade(ProgramGrade $getProgramGrade)
    {
        $existant = $getProgramGrade->getCompanyPrograms()->toArray();
        $companies = array_map(fn(CompanyProgram $cp) => $cp->getCompany(), $existant);

        $qb = $this->createQueryBuilder('c');
        if (count($companies) > 0) {
            $qb
                ->where('c NOT IN (:companies)')
                ->setParameter('companies', $companies);
        }

        $qb
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('c.code', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
