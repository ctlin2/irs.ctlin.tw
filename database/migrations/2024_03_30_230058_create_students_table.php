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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('std_name', 30);
            $table->string('std_no', 30);
            $table->foreignId('group_id')->nullable()->constrained();
            $table->foreignId('course_id')->constrained(); // ToDo: student can take mnay course
            $table->foreignId('user_id')->nullable()->constrained(); // added by C.T.Lin
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['course_id']);
        });
        Schema::dropIfExists('students');
    }
};
