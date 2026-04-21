<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class NoProfanity extends Constraint
{
    public string $message = 'Inappropriate language detected. Unacceptable words: "{{ words }}". Please maintain a respectful tone.';

    // If the constraint has options, defined them here
}
