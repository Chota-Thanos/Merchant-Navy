<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Question imports (PDF / PDF with AI / Excel).
 * A file is parsed into DRAFT questions that wait here for the admin's review.
 * Nothing reaches the real question bank until the admin sends it (see QuestionImportService::commit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('test_id')->nullable()->constrained('tests')->nullOnDelete(); // the test it was started from
            $table->string('method', 20);                   // App\Enums\ImportMethod: pdf | pdf_ai | excel
            $table->string('original_name');
            $table->string('file_path');                    // private disk
            $table->string('status', 20)->default('parsing'); // parsing | ready | failed
            $table->unsignedTinyInteger('progress')->default(0); // 0–100, shown while the AI works
            $table->json('summary')->nullable();            // counts, expected total, notes
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('import_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');            // order in the file
            $table->unsignedInteger('number')->nullable();  // "Q72" in the file
            $table->string('part')->nullable();             // "Part 3"
            $table->string('kind')->nullable();             // concept / calculation
            $table->text('body');                           // HTML (+ LaTeX)
            $table->text('option_1')->nullable();
            $table->text('option_2')->nullable();
            $table->text('option_3')->nullable();
            $table->text('option_4')->nullable();
            $table->unsignedTinyInteger('correct')->nullable(); // 1–4
            $table->text('explanation')->nullable();
            $table->string('difficulty', 10)->nullable();   // easy | medium | hard
            $table->unsignedSmallInteger('page')->nullable(); // page in the PDF where it starts
            $table->json('warnings')->nullable();           // [{level: error|warning, message}]
            $table->string('status', 10)->default('pending'); // pending | added | skipped
            $table->foreignId('question_id')->nullable()->constrained('questions')->nullOnDelete(); // created/reused question
            $table->timestamps();
            $table->index(['question_import_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_questions');
        Schema::dropIfExists('question_imports');
    }
};
