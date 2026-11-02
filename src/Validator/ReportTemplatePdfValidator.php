<?php
/*
  Copyright (C) 2026: Luis Ramón López López

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

namespace App\Validator;

use App\Service\PdfTemplateValidationError;
use App\Service\PdfTemplateValidator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ReportTemplatePdfValidator extends ConstraintValidator
{
    public function __construct(private readonly PdfTemplateValidator $validator)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ReportTemplatePdf) {
            throw new UnexpectedTypeException($constraint, ReportTemplatePdf::class);
        }

        // Sin fichero (o con una subida fallida) ya se queja la restricción de campo obligatorio o de fichero
        if (!$value instanceof UploadedFile || !$value->isValid()) {
            return;
        }

        $error = $this->validator->validate((string) file_get_contents($value->getPathname()));

        $message = match ($error) {
            PdfTemplateValidationError::InvalidPdf => $constraint->invalidMessage,
            PdfTemplateValidationError::MultiPage => $constraint->multiPageMessage,
            null => null,
        };

        if ($message !== null) {
            $this->context->buildViolation($message)
                ->setTranslationDomain('edu_report_template')
                ->addViolation();
        }
    }
}
