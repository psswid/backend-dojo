<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PersonaConfigTest extends TestCase
{
    private function config(): array
    {
        return require dirname(__DIR__, 2).'/config/personas.php';
    }

    public function test_default_persona_exists(): void
    {
        $config = $this->config();

        $this->assertArrayHasKey('default', $config);
        $this->assertArrayHasKey($config['default'], $config['personas']);
    }

    public function test_expected_personas_are_defined(): void
    {
        $personas = array_keys($this->config()['personas']);

        foreach (['explainer', 'interviewer', 'evaluator', 'gap_analyzer'] as $expected) {
            $this->assertContains($expected, $personas, "missing persona: {$expected}");
        }
    }

    public function test_every_persona_has_required_fields(): void
    {
        foreach ($this->config()['personas'] as $key => $persona) {
            $this->assertArrayHasKey('name', $persona, "{$key}: name");
            $this->assertArrayHasKey('icon', $persona, "{$key}: icon");
            $this->assertArrayHasKey('description', $persona, "{$key}: description");
            $this->assertArrayHasKey('system', $persona, "{$key}: system");
            $this->assertNotEmpty($persona['system'], "{$key}: system prompt empty");
        }
    }
}
