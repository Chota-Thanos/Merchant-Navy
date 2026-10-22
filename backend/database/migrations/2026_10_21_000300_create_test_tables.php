<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Practice & mock tests, the question bank and student attempts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('kind', 20); // App\Enums\TestKind: practice | mock
            $table->unsignedSmallInteger('duration_minutes');
            $table->decimal('marks_correct', 5, 2)->default(4);
            $table->decimal('marks_wrong', 5, 2)->default(1);
            $table->text('instructions')->nullable(); // extra instructions shown before the test
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        // Question bank: a question can be reused in many tests.
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');        // HTML + LaTeX
            $table->text('explanation')->nullable(); // HTML + LaTeX
            $table->string('difficulty', 10)->nullable(); // easy | medium | hard
            $table->timestamps();
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position'); // 1..4
            $table->text('body');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });

        Schema::create('test_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->unique(['test_id', 'question_id']);
        });

        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // public id used in URLs
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('attempt_no');
            $table->string('status', 20)->default('in_progress'); // App\Enums\AttemptStatus
            $table->timestamp('started_at');
            $table->timestamp('deadline_at');
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedInteger('time_taken_seconds')->nullable();
            $table->unsignedSmallInteger('correct')->default(0);
            $table->unsignedSmallInteger('incorrect')->default(0);
            $table->unsignedSmallInteger('skipped')->default(0);
            $table->decimal('score', 8, 2)->default(0);
            $table->timestamps();
            $table->index(['test_id', 'attempt_no', 'score']); // rank queries on first attempts
            $table->index(['user_id', 'test_id']);
        });

        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('question_options')->nullOnDelete();
            $table->boolean('marked_for_review')->default(false);
            $table->boolean('visited')->default(true);
            $table->timestamps();
            $table->unique(['test_attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
        Schema::dropIfExists('test_attempts');
        Schema::dropIfExists('test_questions');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('tests');
    }
};
