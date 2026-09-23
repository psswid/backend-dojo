<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Resumable quiz sessions: one row per (user, scope, topic_key) holding the
     * full Livewire component snapshot so an interrupted quiz can be resumed
     * at the exact question, with selection/reveal state and scores intact.
     */
    public function up(): void
    {
        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('scope');               // topic | review
            $table->unsignedBigInteger('topic_key')->default(0); // topic id, 0 = review
            $table->boolean('finished')->default(false);
            $table->json('state');
            $table->timestamps();

            $table->unique(['user_id', 'scope', 'topic_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_sessions');
    }
};
