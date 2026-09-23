<?php

/**
 * Smoke-renders every lesson page (and each module's lessons index) in the
 * current environment, exactly as the routes build them, so template errors
 * surface without a browser. Usage: php scripts/render_lessons_smoke.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Lesson;
use App\Models\Question;
use App\Models\Task;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

$user = User::first();
if ($user) {
    Auth::login($user);
}

$failures = 0;
$rendered = 0;

foreach (Topic::orderBy('sort_order')->get() as $topic) {
    $lessons = $topic->lessons()->with('sections')->get();
    if ($lessons->isEmpty()) {
        echo "skip  {$topic->slug}: no lessons\n";
        continue;
    }

    // Index page.
    try {
        view('lessons.index', [
            'topic' => $topic,
            'lessons' => $topic->lessons()->withCount('sections')->get(),
        ])->render();
        $rendered++;
    } catch (Throwable $e) {
        $failures++;
        echo "FAIL  index {$topic->slug}: ".$e->getMessage()."\n";
    }

    foreach ($lessons as $lesson) {
        $tags = array_values(array_filter((array) $lesson->tags));
        $tasks = Task::where('topic_id', $topic->id)->orderBy('sort_order')->get()
            ->filter(fn (Task $t) => $tags !== [] && ! empty(array_intersect($tags, (array) $t->tags)))
            ->values();
        $questions = Question::where('topic_id', $topic->id)->orderBy('sort_order')->get()
            ->filter(fn (Question $q) => $tags !== [] && ! empty(array_intersect($tags, (array) $q->tags)))
            ->values();

        try {
            $html = view('lessons.show', [
                'topic' => $topic,
                'lesson' => $lesson,
                'prevLesson' => $lesson->prevLesson(),
                'nextLesson' => $lesson->nextLesson(),
                'relatedTasks' => $tasks,
                'relatedQuestions' => $questions,
            ])->render();
            $rendered++;
            $escapedFigure = str_contains($html, '&lt;div class="ld-');
            $figureMarkup = str_contains($html, '<div class="ld-');
            $flag = '';
            if ($escapedFigure || (! $figureMarkup && str_contains($html, 'lesson-diagram'))) {
                $flag = '  <-- FIGURE PAYLOAD PROBLEM';
                $failures++;
            }
            echo "ok    {$topic->slug}/{$lesson->slug} (".strlen($html)." B, {$lesson->sections()->count()} sections)$flag\n";
        } catch (Throwable $e) {
            $failures++;
            echo "FAIL  {$topic->slug}/{$lesson->slug}: ".$e->getMessage()."\n";
        }
    }
}

echo "\nrendered: {$rendered}, failures: {$failures}\n";
exit($failures > 0 ? 1 : 0);
