<?php

namespace App\Validator;

use App\Service\PurgoMalumService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NoProfanityValidator extends ConstraintValidator
{
    public function __construct(
        private PurgoMalumService $purgoMalumService
    ) {}

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof NoProfanity) {
            throw new UnexpectedTypeException($constraint, NoProfanity::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if ($this->purgoMalumService->containsProfanity($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}