<?php

namespace App\Services\Llm;

use Illuminate\Support\Facades\Http;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Throwable;

/**
 * Thin wrapper over the local Qwen3.6-35B-A3B model, served through the
 * Olympus LiteLLM proxy (OpenAI-compatible) and accessed via Prism.
 *
 * The cluster is NOT always online (models start manually via `~/ai-cluster/cluster start`),
 * so every call site must be able to degrade gracefully — use isOnline() first.
 */
class LlmService
{
    public function model(): string
    {
        return (string) config('llm.model', env('LLM_MODEL', 'mars-qwen35b'));
    }

    public function healthUrl(): string
    {
        return (string) env('LLM_HEALTH_URL', 'http://192.168.1.82:4001/health');
    }

    /**
     * Lightweight liveness probe (short timeout) — never blocks long.
     */
    public function isOnline(): bool
    {
        try {
            $response = Http::timeout(3)->connectTimeout(2)->get($this->healthUrl());

            return $response->successful();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Single-shot completion. Returns the generated text.
     *
     * @throws Throwable if the cluster is unreachable or the model errors
     */
    public function complete(string $prompt, ?string $system = null, int $maxTokens = 1024): string
    {
        $request = Prism::text()
            ->using(Provider::OpenAI, $this->model())
            ->withMaxTokens($maxTokens)
            ->withPrompt($prompt);

        if ($system !== null) {
            $request = $request->withSystemPrompt($system);
        }

        return $request->asText()->text;
    }

    /**
     * Multi-turn chat completion (persona conversations).
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     *
     * @throws Throwable
     */
    public function chat(array $messages, ?string $system = null, int $maxTokens = 1024): string
    {
        $request = Prism::text()
            ->using(Provider::OpenAI, $this->model())
            ->withMaxTokens($maxTokens)
            ->withMessages($messages);

        if ($system !== null) {
            $request = $request->withSystemPrompt($system);
        }

        return $request->asText()->text;
    }
}
