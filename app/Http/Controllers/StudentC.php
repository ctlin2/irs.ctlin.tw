<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use DB;
use Carbon\Carbon;

use App\Models\Topic;
use App\Models\Student;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Course_attempt;
use App\Models\Course_quiz;


class StudentC extends BaseController
{

    public function login(Request $req){

        // jin-mo modify
        session()->forget(['std_id']);  // deleting data

        return $this->toLoginPage();
    }


    public function loginPost(Request $req)
    {
        // login only for quiz
        // check login if success,
        // get quiz or no quiz

        // jin-mo modify

        $std_no = $req->get('std_no');
        $course_id = session('course_id');

        $rd = Student::where('course_id', $course_id)->where('std_no', $std_no)->first();

        if (!is_null($rd)) {
            session(['std_id' => $rd->id]);
            return $this->enterQuiz();
        } else {
            return $this->toLoginPage('請確認課程或學號是否正確');
        }


    }


    // jin-mo modify
    public function quiz(Request $req) {
        if ($req->has(['course_id', 'course_date'])) {
            session($req->only(['course_id', 'course_date']));
        }

        if (!$this->hasSessionInfo()) {
//             return redirect('student/login');
            return $this->toLoginPage();
        }

        return $this->enterQuiz();
    }


    public function quizPost(Request $req){
        $q_option_id = $req->get('q_option_id');
        $course_quiz_id = $req->get('course_quiz_id');
        $std_id = session('std_id');

        $quiz = Course_quiz::find($course_quiz_id);
        $cts = Carbon::now();
        $expired_at = Carbon::parse($quiz->expired_at);

        if ($cts->lt($expired_at)) {
           Course_attempt::updateOrCreate(
                ['course_quiz_id' => $course_quiz_id, 'std_id' => $std_id],
                ['q_option_id' => $q_option_id]
           );
        }
    }

    private function toLoginPage (string $msg='') {
        return Inertia::render('student/Login', [
            'e_msg' => $msg,
        ]);
    }

    private function enterQuiz(){
        // need get the quiz

        $quiz = Course_quiz::query();
        $question = null;
        $q_option = null;

        $std_id = session('std_id');
        $course_id = session('course_id');
        $course_date = session('course_date');

        $cts = Carbon::now()->format('Y-m-d H:m:s');    // current timestamp
        $after_ans_quiz_ids = Course_attempt::where('std_id', $std_id)->pluck('course_quiz_id')->all();

        $quiz = $quiz->where('course_id', $course_id)->where('course_date', $course_date);
        $quiz = $quiz->where('expired_at', '>', $cts);
        $quiz = $quiz->whereNotIn('id', $after_ans_quiz_ids);
        $quiz = $quiz->first();

        if (!is_null($quiz)) {
            $question = Question::find($quiz->question_id);
            // using makeHidden method hidden is_correct field
            $q_option = QuestionOption::where('question_id', $quiz->question_id)->get()->makeHidden(['is_correct']);
        }

        return Inertia::render('student/Quiz', [
                'quiz' => $quiz,
                'question' => $question,
                'q_option' => $q_option,
            ]);

    }

    private function hasSessionInfo(){
        return session()->has('std_id');
    }
}
