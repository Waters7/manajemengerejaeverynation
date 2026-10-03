<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An account login: either a valid e-mail address or a simple username (e.g. "admin").
 */
class LoginIdentifier implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;
        $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        $isUsername = preg_match('/^[A-Za-z0-9._-]{3,50}$/', $value) === 1;

        if (! $isEmail && ! $isUsername) {
            $fail('Isi :attribute dengan email yang valid atau username (huruf, angka, titik, minus, underscore; minimal 3 karakter).');
        }
    }
}
