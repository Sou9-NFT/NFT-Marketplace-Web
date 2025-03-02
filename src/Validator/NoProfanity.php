<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class NoProfanity extends Constraint
{
    public string $message = 'The text contains inappropriate language.';
}