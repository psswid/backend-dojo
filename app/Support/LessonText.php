<?php

namespace App\Support;

/**
 * Tiny, safe markup helpers for lesson content authored in the content JSONs.
 *
 * Lessons are written as plain text with three conveniences:
 *   `code`      -> inline code span
 *   **bold**    -> <strong>
 *   [label](url)-> external link (https only)
 *
 * Everything is HTML-escaped first; prose blocks ("\n\n"-separated) whose
 * lines all start with "- " render as a bullet list.
 */
class LessonText
{
    /** Inline markup -> safe HTML fragment. */
    public static function inline(string $text): string
    {
        $safe = e($text);

        // Protect inline code first so nothing inside it is re-interpreted.
        $codes = [];
        $safe = preg_replace_callback('/`([^`]+)`/', function ($m) use (&$codes) {
            $codes[] = $m[1];
            return "\x1E".(count($codes) - 1)."\x1F";
        }, $safe) ?? $safe;

        $safe = preg_replace('/\*\*([^*\n]+)\*\*/', '<strong>$1</strong>', $safe) ?? $safe;
        $safe = preg_replace_callback(
            '/\[([^\]\n]+)\]\((https?:\/\/[^\s)]+)\)/',
            fn ($m) => '<a href="'.e($m[2]).'" target="_blank" rel="noopener" class="lesson-link">'.$m[1].'</a>',
            $safe
        ) ?? $safe;

        return preg_replace_callback('/\x1E(\d+)\x1F/', function ($m) use ($codes) {
            $body = $codes[(int) $m[1]] ?? '';
            return '<code class="lesson-code">'.$body.'</code>';
        }, $safe) ?? $safe;
    }

    /** Paragraphs + bullet lists of inline markup -> safe block HTML. */
    public static function prose(string $text): string
    {
        $blocks = preg_split('/\n\s*\n/', trim($text)) ?: [];

        $out = '';
        foreach ($blocks as $block) {
            $lines = preg_split('/\n/', trim($block)) ?: [];
            $bullets = array_filter($lines, fn ($l) => str_starts_with(ltrim($l), '- '));

            if ($bullets !== [] && count($bullets) === count($lines)) {
                $items = array_map(fn ($l) => '<li>'.static::inline(trim(substr(ltrim($l), 2))).'</li>', $lines);
                $out .= '<ul class="lesson-list">'.implode('', $items).'</ul>';
                continue;
            }

            $out .= '<p>'.static::inline($block).'</p>';
        }

        return $out;
    }
}
