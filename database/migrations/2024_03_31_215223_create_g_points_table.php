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
        Schema::create('g_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained();
            $table->foreignId('course_id')->constrained();
            $table->date('course_date');
            $table->smallInteger('g_point'); // 分組積點
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('g_points', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['course_id']);
        });
        Schema::dropIfExists('g_points');
    }
};
