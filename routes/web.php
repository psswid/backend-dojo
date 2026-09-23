<?php

use App\Models\Lesson;
use App\Models\Question;
use App\Models\Task;
use App\Models\Topic;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('topics', 'topics')
    ->middleware(['auth', 'verified'])
    ->name('topics');

Route::get('topics/{topic:slug}', fn (Topic $topic) => view('quiz', ['topic' => $topic]))
    ->middleware(['auth', 'verified'])
    ->name('quiz.topic');

Route::get('topics/{topic:slug}/lessons', function (Topic $topic) {
    return view('lessons.index', [
        'topic' => $topic,
        'lessons' => $topic->lessons()->withCount('sections')->get(),
    ]);
})
    ->middleware(['auth', 'verified'])
    ->name('topics.lessons.index');

Route::get('topics/{topic:slug}/lessons/{lessonSlug}', function (Topic $topic, string $lessonSlug) {
    $lesson = $topic->lessons()->with('sections')->where('slug', $lessonSlug)->firstOrFail();

    $tags = array_values(array_filter((array) $lesson->tags));

    $ranked = fn (array $items) => collect($items)
        ->filter(fn (array $m) => $m['overlap'] > 0)
        ->sort(function (array $a, array $b) {
            if ($a['overlap'] !== $b['overlap']) {
                return $b['overlap'] <=> $a['overlap'];
            }
            return $a['sort_order'] <=> $b['sort_order'];
        })
        ->values();

    $scoredTasks = $ranked(Task::where('topic_id', $topic->id)
        ->orderBy('sort_order')
        ->get()
        ->map(fn (Task $task) => [
            'task' => $task,
            'overlap' => count(array_intersect($tags, (array) $task->tags)),
            'sort_order' => $task->sort_order,
        ])->all());

    $scoredQuestions = $ranked(Question::where('topic_id', $topic->id)
        ->orderBy('sort_order')
        ->get()
        ->map(fn (Question $question) => [
            'question' => $question,
            'overlap' => count(array_intersect($tags, (array) $question->tags)),
            'sort_order' => $question->sort_order,
        ])->all());

    return view('lessons.show', [
        'topic' => $topic,
        'lesson' => $lesson,
        'prevLesson' => $lesson->prevLesson(),
        'nextLesson' => $lesson->nextLesson(),
        'relatedTasks' => $scoredTasks->pluck('task')->take(8),
        'relatedQuestions' => $scoredQuestions->pluck('question')->take(6),
    ]);
})
    ->middleware(['auth', 'verified'])
    ->name('topics.lessons.show');

Route::view('review', 'quiz', ['topic' => null])
    ->middleware(['auth', 'verified'])
    ->name('quiz.review');

Route::view('tasks', 'tasks')
    ->middleware(['auth', 'verified'])
    ->name('tasks.index');

Route::get('tasks/{task}', fn (Task $task) => view('task', ['task' => $task]))
    ->middleware(['auth', 'verified'])
    ->name('tasks.show');

Route::view('mentor', 'mentor')
    ->middleware(['auth', 'verified'])
    ->name('mentor');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
