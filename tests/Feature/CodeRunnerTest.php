<?php

namespace Tests\Feature;

use App\Services\CodeRunner;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CodeRunnerTest extends TestCase
{
    public function test_it_parses_a_successful_runner_response(): void
    {
        Http::fake([
            'runner:8080/run' => Http::response([
                'stdout' => "PASS: a\nPASS: b\n",
                'exit_code' => 0,
                'passed' => true,
            ], 200),
        ]);

        config(['runner.url' => 'http://runner:8080']);

        $result = app(CodeRunner::class)->run('fn', "expect('a', 1, 1);");

        $this->assertTrue($result['passed']);
        $this->assertSame(0, $result['exit_code']);
        $this->assertStringContainsString('PASS', $result['stdout']);
    }

    public function test_it_gracefully_handles_runner_down(): void
    {
        Http::fake([
            'runner:8080/run' => Http::response('', 500),
        ]);

        config(['runner.url' => 'http://runner:8080']);

        $result = app(CodeRunner::class)->run('fn', 'test');

        $this->assertFalse($result['passed']);
        $this->assertSame(-1, $result['exit_code']);
    }
}
