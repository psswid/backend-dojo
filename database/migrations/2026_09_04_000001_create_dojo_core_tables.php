<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backend Dojo core schema: curriculum (topics/questions/resources/tasks),
     * learning records (attempts/reviews), and AI-agent tables.
     */
    public function up(): void
    {
        // ---- Curriculum ----

        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->char('color', 7)->default('#6366f1');
            $table->string('icon')->default('📘');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('topics')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('sort_order');
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // single | multi | open
            $table->text('prompt');
            $table->json('options')->nullable();   // array of strings (single/multi)
            $table->json('correct')->nullable();   // array of indexes (single/multi)
            $table->text('explanation');
            $table->string('difficulty')->default('medium'); // easy | medium | hard
            $table->json('tags')->nullable();
            $table->string('source')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['topic_id', 'difficulty']);
        });

        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('url');
            $table->string('type')->default('docs'); // docs | article | video | book
            $table->text('summary')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['topic_id', 'question_id']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // snippet | scenario | debug
            $table->string('title');
            $table->text('description');
            $table->longText('starter_code')->nullable();
            $table->longText('test_suite')->nullable();
            $table->longText('solution')->nullable();
            $table->json('rubric')->nullable();
            $table->string('difficulty')->default('medium');
            $table->json('tags')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ---- Learning records ----

        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('answer')->nullable();      // submitted answer (selected indexes / text)
            $table->boolean('is_correct')->nullable();
            $table->float('score')->nullable();      // 0..1 (open questions)
            $table->unsignedInteger('time_taken_seconds')->default(0);
            $table->json('ai_feedback')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'question_id']);
            $table->index('created_at');
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->float('ease_factor')->default(2.5);
            $table->unsignedInteger('interval_days')->default(0);
            $table->unsignedInteger('repetitions')->default(0);
            $table->unsignedInteger('lapses')->default(0);
            $table->string('state')->default('new'); // new | learning | review | mastered
            $table->timestamp('due_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'question_id']);
            $table->index('due_at');
        });

        // ---- AI agent ----

        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('persona'); // explainer | interviewer | evaluator | gap_analyzer
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->string('role'); // user | assistant | system
            $table->text('content');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
        });

        Schema::create('insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type')->default('gap_analysis'); // gap_analysis | improvement | summary
            $table->json('content');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('interview_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('company')->nullable();
            $table->string('role')->nullable();
            $table->date('interview_date')->nullable();
            $table->text('raw_notes')->nullable();
            $table->json('structured')->nullable(); // questions classified to topics
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_logs');
        Schema::dropIfExists('insights');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('attempts');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('resources');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('topics');
    }
};
