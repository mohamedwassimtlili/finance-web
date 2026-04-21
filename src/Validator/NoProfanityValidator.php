<?php

namespace App\Validator;

use App\Service\ProfanityFilterService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class NoProfanityValidator extends ConstraintValidator
{
    private ProfanityFilterService $profanityFilterService;

    public function __construct(ProfanityFilterService $profanityFilterService)
    {
        $this->profanityFilterService = $profanityFilterService;
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoProfanity) {
            throw new UnexpectedTypeException($constraint, NoProfanity::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) to handle them
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $detectedWords = $this->profanityFilterService->getDetectedBadWords($value);

        if (!empty($detectedWords)) {
            // Found a bad word! Add a violation on this field
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ words }}', implode(', ', $detectedWords))
                ->addViolation();
        }
    }
}
