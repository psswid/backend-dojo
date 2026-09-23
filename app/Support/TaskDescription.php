<?php

namespace App\Support;

/**
 * Turns a single-line task description into structured blocks so the task
 * page can render it readably (signature / rules / examples / hint / body)
 * instead of one uniform wall of text.
 *
 * The seed descriptions are loose prose, so parsing is deliberately defensive:
 * every extraction is optional and nothing is thrown away — unrecognized text
 * simply stays in the body. Markers ("Examples:", "Rules:", "Hint:") may appear
 * in any order; sections are scanned left to right with a quote/paren-aware
 * scanner so separators inside string literals or array literals never split.
 */
class TaskDescription
{
    /** @return array<int, array{type: string, text?: string, items?: array<int, array{call?: string, output?: string, text?: string}>}> */
    public static function blocks(string $raw): array
    {
        $body = trim($raw);

        if ($body === '') {
            return [];
        }

        $rules = [];
        $examples = [];
        $hint = '';

        // Locate the markers and process their regions in document order.
        $occurrences = [];
        foreach (['examples' => 'Examples?', 'rules' => 'Rules?', 'hint' => '(?:Bug\s+)?hint'] as $kind => $word) {
            if (preg_match_all('/\b'.$word.'\s*[:—-]\s*/i', $body, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $m) {
                    $occurrences[] = ['kind' => $kind, 'start' => $m[1], 'end' => $m[1] + strlen($m[0])];
                }
            }
        }

        if ($occurrences === []) {
            $signature = self::extractSignature($body);
            if ($signature !== null) {
                return [
                    ['type' => 'signature', 'text' => $signature],
                    ['type' => 'body', 'text' => trim(ltrim(substr($body, strlen($signature)), " \t.,;:–—-"))],
                ];
            }

            return [['type' => 'body', 'text' => $body]];
        }

        usort($occurrences, fn ($a, $b) => $a['start'] <=> $b['start']);

        $proseParts = [];
        $cursor = 0;

        foreach ($occurrences as $i => $occurrence) {
            $start = $occurrence['start'];
            $end = $occurrence['end'];

            if ($start > $cursor && trim(substr($body, $cursor, $start - $cursor)) !== '') {
                $proseParts[] = trim(substr($body, $cursor, $start - $cursor));
            }

            $regionEnd = $occurrences[$i + 1]['start'] ?? strlen($body);
            $region = trim(substr($body, $end, max(0, $regionEnd - $end)));

            switch ($occurrence['kind']) {
                case 'examples':
                    [$items, $trailing] = self::extractExamples($region);
                    $examples = array_merge($examples, $items);
                    if ($trailing !== '') {
                        $proseParts[] = trim($trailing);
                    }
                    break;

                case 'rules':
                    $rules = array_merge($rules, array_map(
                        fn (string $r) => ['text' => $r],
                        self::splitOutsideQuotes($region, ';')
                    ));
                    break;

                case 'hint':
                    $hint = trim($hint.' '.$region);
                    break;
            }

            $cursor = $regionEnd;
        }

        $tail = trim(substr($body, $cursor));
        if ($tail !== '') {
            $proseParts[] = $tail;
        }

        $blocks = [];

        $prose = implode(' ', $proseParts);
        $signature = self::extractSignature($prose);
        if ($signature !== null) {
            $blocks[] = ['type' => 'signature', 'text' => $signature];
            $prose = trim(ltrim(substr($prose, strlen($signature)), " \t.,;:–—-"));
        }
        if ($prose !== '') {
            $blocks[] = ['type' => 'body', 'text' => $prose];
        }
        if ($rules !== []) {
            $blocks[] = ['type' => 'rules', 'items' => $rules];
        }
        if ($examples !== []) {
            $blocks[] = ['type' => 'examples', 'items' => $examples];
        }
        if ($hint !== '') {
            $blocks[] = ['type' => 'hint', 'text' => $hint];
        }

        return $blocks;
    }

    /**
     * Split an "Examples:" region into items and any trailing prose.
     *
     * @return array{0: array<int, array{call: string, output: string}>, 1: string}
     */
    private static function extractExamples(string $region): array
    {
        $items = [];
        $rest = $region;
        $consumed = 0;

        while ($rest !== '') {
            $sep = self::findSeparator($rest);
            if ($sep === null) {
                break; // leftover text is not a clean example
            }

            $call = trim(substr($rest, 0, $sep['pos']), " \t;.,…");
            $rest = substr($rest, $sep['pos'] + $sep['len']);
            $consumed = strlen($region) - strlen($rest);

            // Output runs until a TOP-LEVEL comma/semicolon/period (array
            // literals and parens inside the output must not split it).
            $output = '';
            $stop = null;
            $depth = 0;
            $quote = null;
            $len = strlen($rest);
            for ($i = 0; $i < $len; $i++) {
                $ch = $rest[$i];
                if ($quote !== null) {
                    if ($ch === $quote && $rest[$i - 1] !== '\\') {
                        $quote = null;
                    }
                    continue;
                }
                if ($ch === "'" || $ch === '"') {
                    $quote = $ch;
                    continue;
                }
                if ($ch === '(' || $ch === '[') {
                    $depth++;
                    continue;
                }
                if ($ch === ')' || $ch === ']') {
                    $depth = max(0, $depth - 1);
                    continue;
                }
                if ($depth === 0 && ($ch === ',' || $ch === ';' || $ch === '.')) {
                    $stop = $i;
                    break;
                }
            }

            if ($stop === null) {
                $output = rtrim($rest, " \t;.,…");
                $rest = '';
            } else {
                $output = rtrim(substr($rest, 0, $stop), " \t");
                $rest = ltrim(substr($rest, $stop + 1), " \t;.,…");
            }
            $consumed = strlen($region) - strlen($rest);

            if ($call !== '' && $output !== '') {
                $items[] = ['call' => $call, 'output' => $output];
            }
        }

        return [$items, trim(substr($region, $consumed))];
    }

    /**
     * Find the first "returns / must return / === / ==" separator that sits at
     * bracket/quote depth zero. Returns position and matched length, or null.
     *
     * @return array{pos: int, len: int}|null
     */
    private static function findSeparator(string $text): ?array
    {
        $depth = 0;
        $quote = null;
        $len = strlen($text);

        for ($i = 0; $i < $len; $i++) {
            $ch = $text[$i];

            if ($quote !== null) {
                if ($ch === $quote && ($i === 0 || $text[$i - 1] !== '\\')) {
                    $quote = null;
                }
                continue;
            }

            if ($ch === "'" || $ch === '"') {
                $quote = $ch;
                continue;
            }

            if ($ch === '(' || $ch === '[') {
                $depth++;
                continue;
            }
            if ($ch === ')' || $ch === ']') {
                $depth = max(0, $depth - 1);
                continue;
            }

            if ($depth === 0) {
                if (preg_match('/\G\s*(?:returns?|must\s+return)\s+/i', $text, $m, 0, $i)) {
                    return ['pos' => $i, 'len' => strlen($m[0])];
                }
                if (preg_match('/\G\s*(?:===|==)\s+/', $text, $m, 0, $i)) {
                    return ['pos' => $i, 'len' => strlen($m[0])];
                }
            }
        }

        return null;
    }

    /** Split on a separator character, ignoring separators inside quotes/brackets. */
    private static function splitOutsideQuotes(string $text, string $separator): array
    {
        $parts = [];
        $buffer = '';
        $quote = null;
        $depth = 0;
        $length = strlen($text);

        for ($i = 0; $i < $length; $i++) {
            $char = $text[$i];

            if ($quote !== null) {
                $buffer .= $char;
                if ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($char === "'" || $char === '"') {
                $quote = $char;
                $buffer .= $char;
                continue;
            }

            if ($char === '(' || $char === '[') {
                $depth++;
            } elseif ($char === ')' || $char === ']') {
                $depth = max(0, $depth - 1);
            } elseif ($char === $separator && $depth === 0) {
                $parts[] = $buffer;
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        if (trim($buffer) !== '') {
            $parts[] = $buffer;
        }

        return array_values(array_filter(array_map(
            fn ($part) => trim($part, " \t.;…"),
            $parts
        )));
    }

    private static function extractSignature(string $text): ?string
    {
        // "Implement name(...): Type" or a bare "name(...): Type" heading the spec.
        if (preg_match('/^(?:Implement\s+|Write\s+)?[A-Za-z_][A-Za-z0-9_]*\([^()]*\)\s*:\s*[^.,;)\s]+(?:\s*\|\s*[^.,;)\s]+)*/u', $text, $m)) {
            return $m[0];
        }

        // Bare "name(...)" with no return type at the very start.
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*\([^()]*\)/u', $text, $m)) {
            return $m[0];
        }

        return null;
    }
}
