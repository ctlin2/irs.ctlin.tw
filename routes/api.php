<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CourseC;
use App\Http\Controllers\QuizC;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



Route::prefix('teacher')->group(function () {
    Route::get('home',[CourseC::class,'home']);
    Route::post('home',[CourseC::class, 'homePost']);
    Route::post('course', [CourseC::class, 'coursePost']);
    Route::post('question', [QuizC::class,'questionPost']);
    Route::post('quiz', [QuizC::class,'quizPost']);
});