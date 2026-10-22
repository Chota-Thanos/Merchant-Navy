<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ordering + chapter content. Each content row points (polymorphic) to the
 * record holding the actual material: a PDF, video, live class, note, assignment or test.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The course page: subjects and mock tests in the order the admin arranged them.
        Schema::create('curriculum_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->morphs('item'); // item_type = subject | test
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('pdf_documents', function (Blueprint $table) {
            $table->id();
            $table->string('file_path'); // private disk — never publicly reachable
            $table->unsignedSmallInteger('page_count')->nullable();
            $table->timestamp('processed_at')->nullable(); // pages rendered to images
            $table->timestamps();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20); // App\Enums\VideoProvider
            $table->string('provider_ref');  // video id / URL at the provider
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();
        });

        Schema::create('live_classes', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20)->nullable(); // agora (later) | youtube
            $table->timestamp('starts_at');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->json('provider_data')->nullable(); // channel name, join url… (optional for now)
            $table->foreignId('recording_video_id')->nullable()->constrained('videos')->nullOnDelete();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->longText('body'); // HTML + LaTeX
            $table->timestamps();
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->text('instructions');
            $table->string('attachment_path')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['assignment_id', 'user_id']);
        });

        // A chapter's list of content (any number, any order).
        Schema::create('chapter_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // App\Enums\ContentType
            $table->string('title');
            $table->morphs('contentable');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('content_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_content_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->unique(['user_id', 'chapter_content_id']);
        });

        // Laravel database notifications (in-app bell).
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('content_progress');
        Schema::dropIfExists('chapter_contents');
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('live_classes');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('pdf_documents');
        Schema::dropIfExists('curriculum_items');
    }
};
