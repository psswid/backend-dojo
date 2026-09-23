<?php

/**
 * Backend Dojo — isolated PHP code runner.
 *
 * A tiny HTTP service (php -S) that executes user-submitted PHP snippets in a
 * sandboxed container (read-only FS, non-root, no DB/network creds, memory+CPU
 * capped, short timeout) and returns the test output.
 *
 * Endpoints:
 *   GET  /health  -> {"ok":true}
 *   POST /run     -> body {"code": "...", "test": "..."} -> {stdout, exit_code, passed}
 *
 * Grading contract: the test suite uses the injected `expect(name, actual, expected)`
 * helper. All-pass => exit 0 => "passed". Any mismatch => prints FAIL and exits 1.
 */

// ---------- routing ----------
$uri = $_SERVER['REQUEST_URI'] ?? '/';

if (($uri === '/health' || $uri === '/health/') && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    return;
}

if (($uri !== '/run' && $uri !== '/run/') || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'not found']);
    return;
}

// ---------- handle /run ----------
$data = json_decode((string) file_get_contents('php://input'), true);
if (! is_array($data)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'invalid json']);
    return;
}

$code = (string) ($data['code'] ?? '');
$test = (string) ($data['test'] ?? '');

// Defensive: strip a leading `<?php` if the caller included it.
$code = preg_replace('/^<\?php\s*/i', '', $code);
$test = preg_replace('/^<\?php\s*/i', '', $test);

$helper = <<<'PHP'
function expect(string $name, $actual, $expected): void {
    if ($actual === $expected) {
        echo "PASS: {$name}\n";
        return;
    }
    echo "FAIL: {$name} — expected " . var_export($expected, true) . ", got " . var_export($actual, true) . "\n";
    exit(1);
}
PHP;

// User code first, then the helper, then the tests. This keeps the line
// numbers in PHP error messages aligned with the editor (main.php line N+1
// == editor line N; the leading `<?php` occupies line 1).
$main = "<?php\n" . $code . "\n\n" . $helper . "\n\n" . $test . "\n";

// Write to a private temp dir inside the (tmpfs) /tmp.
$dir = sys_get_temp_dir() . '/dojo_' . bin2hex(random_bytes(4));
@mkdir($dir, 0700, true);
$file = $dir . '/main.php';
file_put_contents($file, $main);

// Hard limits: 10s wall clock, 10s CPU, 128M memory.
$cmd = 'timeout 10 php -d memory_limit=128M -d max_execution_time=10 -d display_errors=1 -d error_reporting=E_ALL '
    . escapeshellarg($file) . ' 2>&1';

$output = [];
$exit = 0;
exec($cmd, $output, $exit);

@unlink($file);
@rmdir($dir);

$stdout = implode("\n", $output);
if (strlen($stdout) > 20000) {
    $stdout = substr($stdout, 0, 20000) . "\n... (output truncated)";
}

header('Content-Type: application/json');
echo json_encode([
    'stdout' => $stdout,
    'exit_code' => $exit,
    'passed' => $exit === 0,
]);
