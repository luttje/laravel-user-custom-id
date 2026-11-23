<?php

namespace Luttje\UserCustomId\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Luttje\UserCustomId\Exceptions\InvalidFormatException;
use Luttje\UserCustomId\UserCustomIdManager;

/**
 * Validation rule for a format that a user submitted.
 *
 * E.g: $request->validate(['format' => ['required', new ValidFormat]]);
 */
class ValidFormat implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        try {
            app(UserCustomIdManager::class)->validateFormat($value);
        } catch (InvalidFormatException $e) {
            $fail($e->getMessage());
        }
    }
}
