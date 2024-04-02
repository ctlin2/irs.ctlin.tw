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
        // course quiz can contain only one question.
        Schema::create('course_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained();
            $table->float('marks')->default(0); //0 means no marks
            $table->unsignedInteger('max_attempts')->default(2); //0 means unlimited attempts
            $table->tinyInteger('is_published')->default(0); //0 means not published, 1 means published
            // $table->date('course_date'); // useless, used only in attempts 
            $table->timestamp('created_at')->useCurrent(); // valid from
            $table->timestamp('expired_at')->useCurrent(); // valid upto
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_quizzes', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
            $table->dropForeign(['course_id']);
        });
        Schema::dropIfExists('course_quizzes');
    }
};
