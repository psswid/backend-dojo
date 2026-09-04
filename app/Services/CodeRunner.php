<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Client for the isolated PHP sandbox runner (docker/runner).
 */
class CodeRunner
{
    /**
     * Execute user code against a test suite and return the result.
     *
     * @return array{stdout: string, exit_code: int, passed: bool}
     */
    public function run(string $code, string $test): array
    {
        try {
            $response = Http::timeout((int) config('runner.timeout', 15))
                ->post(rtrim((string) config('runner.url'), '/').'/run', [
                    'code' => $code,
                    'test' => $test,
                ]);

            if ($response->failed()) {
                return [
                    'stdout' => "Runner unavailable (HTTP {$response->status()}).",
                    'exit_code' => -1,
                    'passed' => false,
                ];
            }

            $data = $response->json();

            return [
                'stdout' => (string) ($data['stdout'] ?? ''),
                'exit_code' => (int) ($data['exit_code'] ?? -1),
                'passed' => (bool) ($data['passed'] ?? false),
            ];
        } catch (\Throwable $e) {
            return [
                'stdout' => 'Runner unreachable: '.$e->getMessage(),
                'exit_code' => -1,
                'passed' => false,
            ];
        }
    }
}
