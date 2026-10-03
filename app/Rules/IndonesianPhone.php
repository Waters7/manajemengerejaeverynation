<?php

namespace App\Rules;

use App\Services\WhatsApp;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts 08…, 8…, 62… or +62… numbers with 9–13 digits after the country code.
 */
class IndonesianPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = WhatsApp::normalize((string) $value);

        if (! $normalized || ! preg_match('/^62\d{8,13}$/', $normalized)) {
            $fail('Format :attribute belum sesuai, contoh: 081234567890.');
        }
    }
}
