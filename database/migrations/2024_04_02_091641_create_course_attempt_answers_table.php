<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_attempt_id')->nullable()->constrained('course_attempts')->cascadeOnDelete();
            $table->foreignId('quiz_question_id')->nullable()->constrained('questions')->cascadeOnDelete();
            $table->foreignId('question_option_id')->nullable()->constrained('question_options')->cascadeOnDelete(); // for multi-choice question
            $table->string('answer')->nullable(); // for fill-in question
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_attempt_answers', function (Blueprint $table) {
            $table->dropForeign(['course_attempt_id']);
            $table->dropForeign(['quiz_question_id']);
            $table->dropForeign(['question_option_id']);
        });
        Schema::dropIfExists('course_attempt_answers');
    }
};
