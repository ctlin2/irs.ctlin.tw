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
        Schema::create('s_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('std_id')->constrained(
                table: 'students'//, indexName: 's_points_student_id'
            );
            $table->foreignId('group_id')->nullable()->constrained();
            $table->unsignedSmallInteger('status')->default(0);
            $table->foreignId('course_id')->constrained;
            $table->date('course_date');
            $table->smallInteger('s_point'); // 個人積點
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('s_points', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['std_id']);
        });
        Schema::dropIfExists('s_points');
    }
};
