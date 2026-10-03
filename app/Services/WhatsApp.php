<?php

namespace App\Services;

/**
 * WhatsApp deep-link helper: number normalisation (08… → 628…) and template rendering.
 */
class WhatsApp
{
    public function __construct(private Settings $settings) {}

    /**
     * Normalise an Indonesian phone number to international format without "+".
     * 081234567890 → 6281234567890, +62 812-3456 → 628123456, 81234 → 6281234.
     */
    public static function normalize(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.ltrim($digits, '0');
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }

    /** Pretty display: 6281234567890 → 0812-3456-7890 */
    public static function display(?string $number): string
    {
        $normalized = self::normalize($number);
        if (! $normalized) {
            return '';
        }
        $local = str_starts_with($normalized, '62') ? '0'.substr($normalized, 2) : $normalized;

        return trim(chunk_split($local, 4, '-'), '-');
    }

    public function link(?string $number, ?string $message = null): ?string
    {
        $normalized = self::normalize($number);
        if (! $normalized) {
            return null;
        }

        return 'https://wa.me/'.$normalized.($message ? '?text='.rawurlencode($message) : '');
    }

    /**
     * Render a template stored in settings, replacing {placeholders}.
     *
     * @param  array<string, string|null>  $variables
     */
    public function render(string $templateKey, array $variables): string
    {
        $template = (string) $this->settings->get($templateKey, '');

        return strtr($template, collect($variables)->mapWithKeys(fn ($value, $key) => ['{'.$key.'}' => (string) $value])->all());
    }

    /**
     * @param  array<string, string|null>  $variables
     */
    public function templateLink(?string $number, string $templateKey, array $variables): ?string
    {
        return $this->link($number, $this->render($templateKey, $variables));
    }
}
