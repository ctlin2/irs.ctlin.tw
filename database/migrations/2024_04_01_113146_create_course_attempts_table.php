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
        Schema::create('course_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_quiz_id')->constrained(
                table: 'course_quizzes'
            )->onDelete('cascade');
            $table->foreignId('std_id')->constrained(  // participant
                table: 'students'
            )->onDelete('cascade');
            // $table->string('participant_type');
            // $table->foreignId('q_option_id')->constrained(
            //     table: 'question_options'
            // );
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_attempts', function (Blueprint $table) {
            $table->dropForeign(['course_quiz_id']);
            $table->dropForeign(['std_id']);
        });
        Schema::dropIfExists('course_attempts');
    }
};
