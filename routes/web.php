<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\CourseC;
use App\Http\Controllers\PointC;
use App\Http\Controllers\QuizC;
use App\Http\Controllers\StudentC;
use App\Http\Controllers\TedC;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\LoginC;
//use App\Http\Middleware\KSUAuthMiddleware;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/teacher/login',[LoginC::class,'teacherLogin']);
Route::get('/student/login',[LoginC::class,'studentLogin']);
Route::post('/teacher/login',[LoginC::class,'teacherLoginPost']);
Route::post('/student/login',[LoginC::class,'studentLoginPost']);


Route::middleware('ksu')->group(function () {
    Route::prefix('teacher')->group(function () {
        Route::get('course', [CourseC::class, 'course']);
        Route::get('home', [CourseC::class, 'home']);
        Route::get('ranking_list', [PointC::class, 'pointBoard']);

        Route::post('course', [CourseC::class, 'coursePost']);

        Route::get('ted', [TedC::class, 'ted']);
        Route::get('readJson', [TedC::class, 'readJson']);

        Route::post('home', [CourseC::class, 'homePost']);


        Route::get('topic', [QuizC::class, 'topic']);
        Route::post('topic', [QuizC::class, 'topicPost']);


        Route::get('question', [QuizC::class, 'question']);
        Route::post('question', [QuizC::class, 'questionPost']);


        Route::get('quiz', [QuizC::class, 'quiz']);
        Route::post('quiz', [QuizC::class, 'quizPost']);

        Route::get('quiz_answer_detail', [QuizC::class, 'quizAnswerDetail']);

//        , function () {
//        return Inertia::render('teacher/Course');
//    });
    });
//    ->middleware('ksu');
});

Route::prefix('student')->group(function(){
   Route::get('login',[StudentC::class,'login']);
   Route::post('login',[StudentC::class,'loginPost']);
   Route::get('quiz',[StudentC::class,'quiz']);
   Route::post('quiz',[StudentC::class,'quizPost']);
});


Route::get('/home', function () {
    return Inertia::render('Home');
});



//Route

require __DIR__.'/auth.php';
