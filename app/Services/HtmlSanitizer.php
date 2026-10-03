<?php

namespace App\Services;

/**
 * Minimal allow-list sanitiser for CMS rich text (pages, devotionals, event descriptions).
 * Keeps basic formatting, removes scripts, event handlers and javascript: URLs.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><a><ul><ol><li><h2><h3><h4><blockquote><hr><img><figure><figcaption>';

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        // Plain text input: turn blank-line separated paragraphs into <p>.
        if ($html === strip_tags($html)) {
            return collect(preg_split('/\R{2,}/', trim($html)))
                ->map(fn ($paragraph) => '<p>'.nl2br(e(trim($paragraph)), false).'</p>')
                ->implode("\n");
        }

        $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html);
        $html = strip_tags($html, self::ALLOWED_TAGS);
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/(href|src)\s*=\s*(["\']?)\s*(javascript|data|vbscript):[^"\'>\s]*\2/i', '$1="#"', $html);
        $html = preg_replace('/\s+style\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $html);

        return trim($html);
    }
}
