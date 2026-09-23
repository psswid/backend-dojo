<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\LessonSection;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\CurriculumSeeder;
use Database\Seeders\TaskSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonsTest extends TestCase
{
    use RefreshDatabase;

    private function seedAll(): void
    {
        $this->seed(CurriculumSeeder::class);
        $this->seed(TaskSeeder::class);
    }

    private function phpTopic(): \App\Models\Topic
    {
        return \App\Models\Topic::where('slug', 'php-plain-code')->firstOrFail();
    }

    public function test_curriculum_seeder_creates_php_plain_code_lessons(): void
    {
        $this->seedAll();

        $topic = $this->phpTopic();
        $this->assertSame(8, $topic->lessons()->count());

        $lesson = $topic->lessons()->where('slug', 'checksum-validators')->first();
        $this->assertNotNull($lesson);
        $this->assertSame('hard', $lesson->difficulty);
        $this->assertSame(12, $lesson->minutes);
        $this->assertContains('luhn', $lesson->tags);
        $this->assertGreaterThan(10, $lesson->sections()->count());

        // Sections keep stable, ordered keys.
        $first = $lesson->sections()->orderBy('sort_order')->first();
        $this->assertSame('hook', $first->key);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seedAll();

        $lessons = Lesson::count();
        $sections = LessonSection::count();

        $this->seed(CurriculumSeeder::class);
        $this->seed(TaskSeeder::class);

        $this->assertSame($lessons, Lesson::count());
        $this->assertSame($sections, LessonSection::count());
        $this->assertSame(8, $this->phpTopic()->lessons()->count());
    }

    public function test_lessons_pages_require_auth(): void
    {
        $this->seedAll();

        $this->get('/topics/php-plain-code/lessons')->assertRedirect('/login');
        $this->get('/topics/php-plain-code/lessons/loose-vs-strict')->assertRedirect('/login');
    }

    public function test_lesson_index_page_lists_lessons(): void
    {
        $this->seedAll();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/topics/php-plain-code/lessons')
            ->assertOk()
            ->assertSee('Type juggling: loose == vs strict ===')
            ->assertSee('Checksum validators: Luhn, ISBN, PESEL, NIP')
            ->assertSee('min read');
    }

    public function test_lesson_show_renders_sections_and_content(): void
    {
        $this->seedAll();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/topics/php-plain-code/lessons/checksum-validators')
            ->assertOk()
            ->assertSee('The recipe every checksum follows')
            ->assertSee('79927398713')
            ->assertSee('4417123456789113')
            ->assertSee('section-luhn'); // anchor id on a heading

        // Step-by-step lesson with state transitions.
        $this->actingAs($user)
            ->get('/topics/php-plain-code/lessons/string-pipelines')
            ->assertOk()
            ->assertSee('slugify')
            ->assertSee('After this step');
    }

    public function test_lesson_show_links_related_tasks_and_questions(): void
    {
        $this->seedAll();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)
            ->get('/topics/php-plain-code/lessons/checksum-validators')
            ->assertOk();

        // luhn / isbn / pesel / nip drills share the lesson's tags.
        foreach (['luhn_check', 'is_valid_pesel', 'is_valid_isbn10'] as $needle) {
            $response->assertSee($needle);
        }
        $response->assertSee('Coding tasks')->assertSee('Quiz questions covering this');
    }

    public function test_figure_sections_render_markup_not_raw_html(): void
    {
        $this->seedAll();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $html = $this->actingAs($user)
            ->get('/topics/php-plain-code/lessons/loose-vs-strict')
            ->assertOk()
            ->getContent();

        // The diagram markup must be live HTML…
        $this->assertStringContainsString('<div class="ld-flow">', $html);
        $this->assertStringContainsString('ld-box ld-r', $html);
        // …not an escaped string shown as text.
        $this->assertStringNotContainsString('&lt;div class="ld-', $html);
        $this->assertStringNotContainsString('class="lesson-diagram">The asymmetry at a glance', $html);
        // And the figure title is plain text in the caption, not markup.
        $this->assertStringContainsString('The asymmetry at a glance: what is loosely equal to null', $html);
    }

    public function test_unknown_topic_or_lesson_returns_404(): void
    {
        $this->seedAll();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/topics/php-plain-code/lessons/does-not-exist')->assertNotFound();
        $this->actingAs($user)->get('/topics/nope/lessons')->assertNotFound();
    }

    public function test_lesson_model_prev_next_walk_through_topic_order(): void
    {
        $this->seedAll();

        $first = Lesson::where('topic_id', $this->phpTopic()->id)->orderBy('sort_order')->first();
        $second = $first->nextLesson();

        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->prevLesson()->id);
    }

    public function test_every_module_has_lessons_after_seeding(): void
    {
        $this->seedAll();

        // Rollout: all 8 topics carry at least one lesson.
        $this->assertSame(8, Topic::has('lessons')->count());
        // Sanity floor for the whole library (php-plain-code alone has 117).
        $this->assertGreaterThan(400, LessonSection::count());
    }

    public function test_matching_tags_returns_only_overlapping_lessons(): void
    {
        $this->seedAll();

        $lessons = Lesson::matchingTags($this->phpTopic()->id, ['luhn', 'checksum']);
        $this->assertTrue($lessons->contains(fn (Lesson $l) => $l->slug === 'checksum-validators'));

        $generatorOnly = Lesson::matchingTags($this->phpTopic()->id, ['generators']);
        $this->assertTrue($generatorOnly->contains(fn (Lesson $l) => $l->slug === 'generators-lazy'));
        $this->assertFalse($generatorOnly->contains(fn (Lesson $l) => $l->slug === 'checksum-validators'));
    }
}
