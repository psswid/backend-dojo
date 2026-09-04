<?php

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

Route::view('review', 'quiz', ['topic' => null])
    ->middleware(['auth', 'verified'])
    ->name('quiz.review');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
