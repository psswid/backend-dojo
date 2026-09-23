<?php

namespace App\Support;

/**
 * Presentation helpers for quiz content.
 *
 * Quiz questions/options are authored as plain single- or multi-line strings.
 * This class turns them into safe, readable HTML blocks:
 *
 *  - prompt: splits scenario questions with "(a) … (b) … (c)" sections into an
 *    enumerated list (keeping the lead-in as prose), and renders `code` spans
 *    in monospace.
 *  - explanation: splits multi-line model answers into paragraphs, lifting
 *    code-shaped blocks (SQL/PHP) into <pre> blocks, and renders `code` spans.
 *
 * Nothing is ever dropped: unrecognised text always falls back to an escaped
 * paragraph.
 */
class QuizText
{
    /**
     * Escape and convert `backticked` spans to <code>.
     */
    public static function inline(string $text): string
    {
        $html = e($text);

        return preg_replace_callback(
            '/`([^`]+)`/',
            fn (array $m) => '<code class="quiz-code">'.$m[1].'</code>',
            $html
        );
    }

    /**
     * Block representation of a question prompt (or any prose that may carry
     * "(a)/(b)/(c)" sections).
     *
     * @return array<int, array{type: string, html?: string, items?: array<int, array{k: string, html: string}>}>
     */
    public static function promptBlocks(string $text): array
    {
        $split = self::splitEnumeration($text);

        if ($split === null) {
            return [['type' => 'p', 'html' => self::inline($text)]];
        }

        [$intro, $items] = $split;
        $blocks = [];

        if ($intro !== '') {
            $blocks[] = ['type' => 'p', 'html' => self::inline($intro)];
        }

        $blocks[] = [
            'type' => 'enum',
            'items' => array_map(
                fn (array $item) => ['k' => $item['k'], 'html' => self::inline($item['text'])],
                $items
            ),
        ];

        return $blocks;
    }

    /**
     * Block representation of a (possibly multi-paragraph) explanation /
     * model answer. Paragraphs are split on blank lines; paragraphs that look
     * like code blocks (SQL / PHP dumps with real line breaks) become <pre>.
     *
     * @return array<int, array{type: string, html: string}>
     */
    public static function explanationBlocks(string $text): array
    {
        $paragraphs = preg_split('/\n[ \t]*\n/', trim($text));
        $blocks = [];

        foreach ($paragraphs as $paragraph) {
            if (trim($paragraph) === '') {
                continue;
            }

            $lines = preg_split('/\r\n|\r|\n/', $paragraph);
            $single = count($lines) <= 1;

            if (! $single && self::looksLikeCodeLines($lines)) {
                $blocks[] = ['type' => 'code', 'html' => e(implode("\n", $lines))];
            } else {
                $blocks[] = ['type' => 'p', 'html' => self::inline($paragraph)];
            }
        }

        if ($blocks === []) {
            $blocks[] = ['type' => 'p', 'html' => e($text)];
        }

        return $blocks;
    }

    /**
     * Best-effort guess: whole option strings that are tiny code expressions
     * (no spaces, or method/static-call/assignment shapes) render in mono.
     */
    public static function isCodeLikeOption(string $text): bool
    {
        $t = trim($text);

        if ($t === '') {
            return false;
        }

        // A true sentence always contains a space and prose words.
        if (preg_match('/\s{2,}/', $t) || preg_match('/\s(and|because|the|which|it|returns|with|so)\s/i', $t)) {
            return false;
        }

        return preg_match('/^(->|::|\$|[A-Za-z_\\\\][\w\\\\]*::|\w+\s*\(|[\'"]|(?:SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER)\b)/i', $t)
            || preg_match('/->|::|===|==|=>/', $t)
            || (! str_contains($t, ' ') && strlen($t) <= 40);
    }

    /**
     * Split "(a)…(b)…(c)…" enumerations used in scenario-style prompts.
     * Returns [introText, items] or null when no contiguous a/b/c list is found.
     */
    protected static function splitEnumeration(string $text): ?array
    {
        if (preg_match_all('/\(([a-zA-Z])\)/', $text, $matches, PREG_OFFSET_CAPTURE) < 2) {
            return null;
        }

        // Only treat markers that start after sentence punctuation (". ? ! :")
        // or at the very beginning — "(a list…" or "(an …" must not trigger.
        $markers = [];
        foreach ($matches[0] as $i => $full) {
            [$token, $offset] = $full;
            $before = substr($text, 0, $offset);
            $trimmedBefore = rtrim($before);
            $prev = $trimmedBefore === '' ? '' : mb_substr($trimmedBefore, -1);
            $letter = strtolower($matches[1][$i][0]);

            if ($trimmedBefore !== '' && ! in_array($prev, ['.', '?', '!', ':', ';'], true)) {
                continue;
            }

            $markers[] = ['letter' => $letter, 'token' => $token, 'offset' => $offset];
        }

        // Require a contiguous run starting at "a" (or the first marker letter),
        // of at least two items, to avoid fragmenting ordinary prose.
        if (count($markers) < 2) {
            return null;
        }

        $start = ord($markers[0]['letter']);
        foreach ($markers as $i => $marker) {
            if (ord($marker['letter']) !== $start + $i) {
                return null;
            }
        }

        $intro = trim(substr($text, 0, $markers[0]['offset']));
        $items = [];

        foreach ($markers as $i => $marker) {
            $end = $markers[$i + 1]['offset'] ?? strlen($text);
            $raw = substr($text, $marker['offset'] + strlen($marker['token']), $end - $marker['offset'] - strlen($marker['token']));
            $items[] = ['k' => $marker['letter'], 'text' => trim($raw, " \t\r\n,;")];
        }

        return [$intro, $items];
    }

    /**
     * Does a multi-line paragraph read like a code block (SQL / PHP)?
     * At least two lines must look code-ish.
     *
     * @param string[] $lines
     */
    protected static function looksLikeCodeLines(array $lines): bool
    {
        $codeLines = 0;

        foreach ($lines as $line) {
            $t = trim($line);

            if ($t === '') {
                continue;
            }

            if (preg_match('/^(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|WITH|FROM|WHERE|SET|VALUES|DB::|\\$|->|#\[|<\?php|\?>|function |public |protected |private |class |use |Illuminate\\\\)/i', $t)) {
                $codeLines++;
            }
        }

        return $codeLines >= 2;
    }
}
