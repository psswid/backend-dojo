<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lessons: structured, readable walkthroughs that complement quiz reveals
     * and coding tasks. Content is versioned JSON (seeders/content/*.json),
     * same convention as questions and tasks.
     */
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->unsignedSmallInteger('minutes')->nullable(); // estimated reading time
            $table->string('difficulty')->default('medium');    // easy | medium | hard
            $table->json('tags')->nullable();                   // concept tags, aligned with questions/tasks
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['topic_id', 'slug']);
            $table->index(['topic_id', 'sort_order']);
        });

        Schema::create('lesson_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('key');                              // stable id used for idempotent seeding + anchors
            $table->string('kind');                             // prose | heading | code | table | steps | callout | figure
            $table->string('title')->nullable();                // used by heading sections and as fallback labels
            $table->json('payload')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['lesson_id', 'key']);
            $table->index('lesson_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_sections');
        Schema::dropIfExists('lessons');
    }
};
