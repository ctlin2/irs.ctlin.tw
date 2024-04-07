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
use App\Models\Course;
use App\Models\Course_attempt;
use App\Models\Course_attempt_answer;
use App\Models\Course_quiz;
use App\Models\User;
use Log;

class StudentC extends BaseController
{

    public function login(Request $req){

        // jin-mo modify
        session()->forget(['std_id']);  // deleting data

        return $this->toLoginPage($req); // modified by C.T.Lin
    }


    public function loginPost(Request $req)
    {
        // login only for quiz
        // check login if success,
        // get quiz or no quiz

        // jin-mo modify

        $std_no = $req->get('std_no');
        // $rd = Student::where('course_id', $course_id)->where('std_no', $std_no)->first();
        $rd_student = Student::where('std_no', $std_no)->first(); // C.T.Lin
        // Log::info('(StudentC/loginPost)$rd='.json_encode($rd));

        // $login_std_no = User::find($req->user()->id)->name;

        if (!is_null($rd_student)) {  // modified by C.T.Lin to avoid user faking ID
            if (is_null($rd_student->user_id)) {  // 學生未綁定user帳號
                $req->merge(['msg'=>'您的 Profile 需填寫正確學號']); // added by C.T.Lin
                return $this->toLoginPage($req);
            } else if ($rd_student->user_id === $req->user()->id){ // 相符
                // find the active quizzes and the corresponding courses
                $quizzes = Course_quiz::where('created_at', '<', Carbon::now())
                            ->where('expired_at', '>', Carbon::now())->get();
                foreach ($quizzes as $quiz) {
                    $rd_course = $rd_student->courses->where('id',$quiz->course_id)->first();
                    if(!is_null($rd_course)){
                        session(['course_id' => $rd_course->id]);
                        break; // found a course quiz
                    }
                }
                session(['std_id' => $rd_student->id]);
                return $this->enterQuiz();
            }  else {
                $req->merge(['msg'=>'請確認是您的正確學號']); // added by C.T.Lin
                // return $this->toLoginPage('請確認課程或學號是否正確');
                return $this->toLoginPage($req);
            }
        } else {
            $req->merge(['msg'=>'請確認課程或學號是否正確']); // added by C.T.Lin
            // return $this->toLoginPage('請確認課程或學號是否正確');
            return $this->toLoginPage($req);
        }

    }


    // jin-mo modify
    public function quiz(Request $req) {
        Log::info('(StudentC/quiz quiz())$req->course_id='.json_encode($req->course_id).', course_date'.json_encode($req->course_date));  // added by C.T.Lin
        // if ($req->has(['course_id', 'course_date'])) {
        //     session($req->only(['course_id', 'course_date'])); 
        //     // Bug: take effects only on teacher's browser, not students' browser.
        // }

        if (!$this->hasSessionInfo()) {
//             return redirect('student/login');
            return $this->toLoginPage($req); // modified C.T.Lin to provide default student number for login.
        }

        return $this->enterQuiz();
    }


    public function quizPost(Request $req){
        $course_quiz_id = $req->get('course_quiz_id');
        $q_option_id = $req->get('q_option_id');  // for single-answer question
        $selected_options = $req->get('selected');  // for multiple-answer question
        $answer = $req->get('answer');  // for fill-in question
        $std_id = session('std_id');

        $quiz = Course_quiz::find($course_quiz_id);
        $cts = Carbon::now();
        $expired_at = Carbon::parse($quiz->expired_at);

        if ($cts->lt($expired_at)) {
            $attempt = Course_attempt::create([
                'course_quiz_id' => $course_quiz_id, 
                'std_id' => $std_id,
            ]);

            // assert $attempt->id not null
            if (!is_null($q_option_id)){  // single-answer question
                $attempt_answer = Course_attempt_answer::Create([
                    'course_attempt_id' => $attempt->id, 
                    'quiz_question_id' => $attempt->question_id,
                    'question_option_id' => $q_option_id
                ]);
            }
            else if (!is_null($answer)){ // fill-in question
                $attempt_answer = Course_attempt_answer::Create([
                    'course_attempt_id' => $attempt->id, 
                    'quiz_question_id' => $attempt->question_id,
                    'answer' => $answer
                ]);
            }
            else { // multiple-answer question
                foreach ($selected_options as $option) {
                    $attempt_answer = Course_attempt_answer::Create([
                        'course_attempt_id' => $attempt->id, 
                        'quiz_question_id' => $attempt->question_id,
                        'question_option_id' => $option
                    ]);
                }
            }
            return back()->with('status', '答案已提交');
        }
        else {
            return back()->with('errors', '作答逾時'); 
        }
    }


    // private function toLoginPage (string $msg='') {
    private function toLoginPage (Request $req) {
        return Inertia::render('student/Login', [
            'e_msg' => $req->get('msg')?? '',
            'std_no' => $req->user()->name
        ]);
    }

    private function enterQuiz(){
        // need get the quiz
        $question = null;
        $q_option = null;

        $std_id = session('std_id');
        $course_id = session('course_id');

        $after_ans_quiz_ids = Course_attempt::where('std_id', $std_id)->pluck('course_quiz_id')->all();
        
        $cts = Carbon::now()->format('Y-m-d H:m:s');
        Log::info('(StudentC/loginPost enerQuiz())NOW='.json_encode(date('Y-m-d H:m:s'))); // 時間一樣，但與系統時間差異過大？為什麼? C.T.Lin
        $quiz = Course_quiz::where('course_id', $course_id)
                // ->whereDate('course_date', Carbon::today()->format('Y-m-d')) // modified by C.T.Lin
                // ->where('created_at', '<=', $cts)
                ->where('expired_at', '>', $cts) 
                ->whereNotIn('id', $after_ans_quiz_ids)
                ->first();

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
