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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('class_name', 30)->default('預設班級');
            $table->string('course_name', 50)->default('預設課程');
            $table->unsignedSmallInteger('def_g_point')->default(50);
            $table->unsignedSmallInteger('def_s_point')->default(0);
            $table->unsignedSmallInteger('def_point_step')->default(5);
            $table->smallInteger('att_status_1')->default(0); // 正常
            $table->smallInteger('att_status_2')->default(-10); // 遲到
            $table->smallInteger('att_status_3')->default(-10); // 早退
            $table->smallInteger('att_status_4')->default(-20); // 遲到早退
            $table->smallInteger('att_status_5')->default(-50); // 缺曠
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
