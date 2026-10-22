<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A mock test can now sit between chapters of a subject. It is stored as a row in the subject's ordered chapter
 * list that points at the test, so ordering, drag-and-drop and student progress need no special handling.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->foreignId('test_id')->nullable()->after('subject_id')->constrained('tests')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('test_id');
        });
    }
};
