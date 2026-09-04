<?php

/**
 * Validates seeded coding tasks:
 *  - snippet/debug: solution MUST pass the test suite (exit 0);
 *  - debug: starter_code MUST fail (confirming the planted bug is real).
 *
 * Run on host: php scripts/validate-tasks.php
 */

$helper = <<<'PHP'
function expect(string $name, $actual, $expected): void {
    if ($actual === $expected) { echo "PASS: {$name}\n"; return; }
    echo "FAIL: {$name} — expected " . var_export($expected, true) . ", got " . var_export($actual, true) . "\n";
    exit(1);
}
PHP;

function runPhp(string $code, string $helper): array
{
    $tmp = tempnam(sys_get_temp_dir(), 'dojo_valid').'.php';
    file_put_contents($tmp, "<?php\n".$helper."\n".$code."\n");
    exec('php '.escapeshellarg($tmp).' 2>&1', $out, $exit);
    @unlink($tmp);

    return [$exit, implode("\n", $out)];
}

$files = glob(__DIR__.'/../database/seeders/content/tasks/*.json');
$ok = 0;
$fail = 0;

foreach ($files as $file) {
    $data = json_decode((string) file_get_contents($file), true);
    if (! is_array($data)) {
        echo "INVALID JSON: ".basename($file)."\n";
        $fail++;
        continue;
    }

    foreach ($data['tasks'] ?? [] as $t) {
        $type = $t['type'] ?? 'snippet';
        $title = $t['title'] ?? 'untitled';
        $label = basename($file).' :: '.$title;

        if ($type === 'scenario') {
            continue;
        }

        // 1. Solution must pass.
        [$exit, $out] = runPhp(($t['solution'] ?? '')."\n".($t['test_suite'] ?? ''), $helper);
        if ($exit !== 0) {
            echo "FAIL (solution) $label\n     ".str_replace("\n", "\n     ", substr($out, 0, 300))."\n";
            $fail++;
            continue;
        }

        // 2. For debug tasks, the broken starter must actually fail.
        if ($type === 'debug') {
            [$exit2] = runPhp(($t['starter_code'] ?? '')."\n".($t['test_suite'] ?? ''), $helper);
            if ($exit2 === 0) {
                echo "WARN (debug starter not broken) $label\n";
            }
        }

        echo "OK   $label\n";
        $ok++;
    }
}

echo "\n{$ok} code task(s) validated, {$fail} failure(s)\n";
exit($fail > 0 ? 1 : 0);
