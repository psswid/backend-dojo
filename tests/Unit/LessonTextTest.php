<?php

namespace Tests\Unit;

use App\Support\LessonText;
use PHPUnit\Framework\TestCase;

class LessonTextTest extends TestCase
{
    public function test_inline_escapes_html(): void
    {
        $html = LessonText::inline('<script>alert(1)</script> & "quotes"');

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&amp;', $html);
    }

    public function test_inline_code_bold_and_links(): void
    {
        $html = LessonText::inline(
            'Use `0 === \'0\'` for **strict** checks — see [the manual](https://www.php.net/manual/en/types.comparisons.php).'
        );

        $this->assertStringContainsString('<code class="lesson-code">0 === &#039;0&#039;</code>', $html);
        $this->assertStringContainsString('<strong>strict</strong>', $html);
        $this->assertStringContainsString(
            '<a href="https://www.php.net/manual/en/types.comparisons.php" target="_blank" rel="noopener" class="lesson-link">the manual</a>',
            $html
        );
    }

    public function test_code_content_is_not_reinterpreted(): void
    {
        $html = LessonText::inline('`**not bold**` still **bold**');

        $this->assertStringContainsString('<code class="lesson-code">**not bold**</code>', $html);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
    }

    public function test_non_http_links_are_ignored(): void
    {
        $html = LessonText::inline('[x](ftp://example.com) [y](javascript:alert(1))');

        $this->assertStringNotContainsString('<a ', $html);
        $this->assertStringContainsString('ftp://example.com', $html);
    }

    public function test_prose_splits_paragraphs_and_bullets(): void
    {
        $html = LessonText::prose("First paragraph.\n\n- one\n- two\n\nLast paragraph.");

        $this->assertSame(2, substr_count($html, '<p>'));
        $this->assertSame(1, substr_count($html, '<ul class="lesson-list">'));
        $this->assertStringContainsString('<li>one</li>', $html);
        $this->assertStringContainsString('<li>two</li>', $html);
    }

    public function test_prose_bullet_detection_requires_all_lines_to_be_bullets(): void
    {
        $html = LessonText::prose("Intro line\n- one\n- two");

        $this->assertStringNotContainsString('<ul', $html);
        $this->assertStringContainsString('<p>', $html);
    }
}
