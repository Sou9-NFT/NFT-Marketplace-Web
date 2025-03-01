<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NoProfanityValidator extends ConstraintValidator
{
    private const PROFANITY_LIST = [
        'fuck', 'shit', 'ass', 'bitch', 'damn', 'cunt', 'dick', 'cock', 'pussy', 'whore',
        'slut', 'bastard', 'piss', 'nigger', 'faggot', 'retard', 'asshole', 'motherfucker',
        // Add more profanity words as needed
    ];

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

        $valueLower = strtolower($value);
        
        foreach (self::PROFANITY_LIST as $word) {
            if (str_contains($valueLower, $word)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ word }}', $word)
                    ->addViolation();
                return;
            }
        }
    }
}