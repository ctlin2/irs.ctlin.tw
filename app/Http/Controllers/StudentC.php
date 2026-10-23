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
                return redirect()->route('student.quiz');
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
        Log::info('(StudentC/quiz quiz())$req->course_id='.json_encode($req->course_id).', course_date='.json_encode($req->course_date));  // added by C.T.Lin
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
        $answer = trim($req->get('answer'));  // for fill-in question
        // $std_id = session('std_id');
        $std_id = Student::where('std_no', '=', $req->user()->name)->first()->id;

        $quiz = Course_quiz::find($course_quiz_id);
        // No matter the attempt is expired or not, it must be recorded. Modified by C.T.Lin

        $attempt = Course_attempt::updateOrCreate([
            'course_quiz_id' => $course_quiz_id, 
            'std_id' => $std_id,
        ],[]);

        $cts = Carbon::now();
        $expired_at = Carbon::parse($quiz->expired_at);

        if ($cts->lt($expired_at)) {
            // assert $attempt->id not null
            if (!is_null($q_option_id)){  // single-answer question
                $attempt_answer = Course_attempt_answer::Create([
                    'course_attempt_id' => $attempt->id, 
                    'quiz_question_id' => $attempt->question_id,
                    'question_option_id' => $q_option_id
                ]);
            }
            else if (!empty($selected_options)) { // multiple-answer question
                foreach ($selected_options as $option) {
                    $attempt_answer = Course_attempt_answer::Create([
                        'course_attempt_id' => $attempt->id, 
                        'quiz_question_id' => $attempt->question_id,
                        'question_option_id' => $option
                    ]);
                }
            }
            else if (!is_null($answer)){ // fill-in question
                $attempt_answer = Course_attempt_answer::Create([
                    'course_attempt_id' => $attempt->id, 
                    'quiz_question_id' => $attempt->question_id,
                    'answer' => $answer
                ]);
            }
            else {
                dd('system error: wrong type of question');
            }
            // session()->flash('message', "作答完成"); // C.T.Lin
            return back()->with('status', '答案已提交');
        }
        else {
            // as if no answer
            return back()->withErrors(['status' => '作答逾時']); // errors
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
        $time_left = null;

        $std_id = session('std_id');
        $course_id = session('course_id');

        $after_ans_quiz_ids = Course_attempt::where('std_id', $std_id)->pluck('course_quiz_id')->all();
        
        $cts = Carbon::now()->toDateTimeString(); // format('Y-m-d H:i:s');
        Log::info('(StudentC/loginPost enterQuiz())NOW='.$cts); // 
        $quiz = Course_quiz::where('course_id', $course_id)
                // ->whereDate('course_date', Carbon::today()->format('Y-m-d')) // modified by C.T.Lin
                // ->where('created_at', '<=', $cts)
                ->where('expired_at', '>', $cts) 
                ->whereNotIn('id', $after_ans_quiz_ids)
                ->orderBy('expired_at', 'ASC')
                ->first();  // TODO, bug?

        if (!is_null($quiz)) {
            $question = Question::find($quiz->question_id);
            // using makeHidden method hidden is_correct field
            $q_option = QuestionOption::where('question_id', $quiz->question_id)->get()->makeHidden(['is_correct']);
            $time_left = Carbon::parse($quiz->expired_at)->diff(Carbon::now())->format('%H:%I:%S');
        }

        return Inertia::render('student/Quiz', [
                'quiz' => $quiz,
                'question' => $question,
                'q_option' => $q_option,
                'time_left' => $time_left ? $time_left : '00:00:00'
            ]);

    }

    public function list(Request $req){
        $course_id = $req->get('course_id');
//        dd($course_id);
        $students = Course::find($course_id)->students;
        return response()->json(['students' => $students])
            ->header('Access-Control-Allow-Origin','*')
            ->header('Access-Control-Allow-Methods',['GET','POST'])
            ->header('Access-Control-Allow-Headers','X-Requested-With, Content-Type');
    }

    public function history(Request $req){
        $student = Student::where('id', session('std_id'))
            ->where('user_id', $req->user()->id)
            ->first();

        if (!$student) {
            session()->forget('std_id');
            return redirect('/student/login');
        }

        $attempts = DB::table('course_attempts as attempt')
            ->join('course_quizzes as quiz', 'attempt.course_quiz_id', '=', 'quiz.id')
            ->join('courses as course', 'quiz.course_id', '=', 'course.id')
            ->join('questions as question', 'quiz.question_id', '=', 'question.id')
            ->where('attempt.std_id', $student->id)
            ->whereNull('attempt.deleted_at')
            ->select(
                'attempt.id as attempt_id',
                'attempt.updated_at as answered_at',
                'quiz.id as quiz_id',
                'quiz.question_id',
                'quiz.created_at as quiz_created_at',
                'course.id as course_id',
                'course.course_name',
                'course.class_name',
                'question.name as question_name',
                'question.answer as expected_answer'
            )
            ->orderByDesc('quiz.created_at')
            ->get();

        $answersByAttempt = collect();
        $optionsByQuestion = collect();
        if ($attempts->isNotEmpty()) {
            $optionsByQuestion = DB::table('question_options')
                ->whereIn('question_id', $attempts->pluck('question_id')->unique())
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get(['id', 'question_id', 'name', 'is_correct'])
                ->groupBy('question_id');

            $answersByAttempt = DB::table('course_attempt_answers as answer')
                ->leftJoin('question_options as option', 'answer.question_option_id', '=', 'option.id')
                ->whereIn('answer.course_attempt_id', $attempts->pluck('attempt_id'))
                ->whereNull('answer.deleted_at')
                ->orderBy('answer.created_at')
                ->get([
                    'answer.course_attempt_id',
                    'answer.question_option_id',
                    'answer.answer as text_answer',
                    'option.name as option_name',
                ])
                ->groupBy('course_attempt_id');
        }

        $history = $attempts->map(function ($attempt) use ($answersByAttempt, $optionsByQuestion) {
            $attemptAnswers = $answersByAttempt->get($attempt->attempt_id, collect());
            $selectedOptionIds = $attemptAnswers->pluck('question_option_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->all();
            $responses = $attemptAnswers
                ->map(fn ($answer) => $answer->option_name ?? $answer->text_answer)
                ->filter(fn ($answer) => $answer !== null && $answer !== '')
                ->values();
            $options = $optionsByQuestion->get($attempt->question_id, collect())
                ->map(fn ($option) => [
                    'id' => $option->id,
                    'name' => $option->name,
                    'is_correct' => (bool) $option->is_correct,
                    'is_selected' => in_array((int) $option->id, $selectedOptionIds, true),
                ])
                ->values();

            return [
                'attempt_id' => $attempt->attempt_id,
                'quiz_id' => $attempt->quiz_id,
                'course_id' => $attempt->course_id,
                'course_name' => $attempt->course_name,
                'class_name' => $attempt->class_name,
                'quiz_date' => substr($attempt->quiz_created_at, 0, 10),
                'question_name' => $attempt->question_name,
                'answers' => $responses,
                'options' => $options,
                'expected_answer' => $attempt->expected_answer,
                'answered_at' => $attempt->answered_at,
            ];
        });

        return Inertia::render('student/History', [
            'history' => $history,
            'student' => [
                'std_no' => $student->std_no,
                'std_name' => $student->std_name,
            ],
        ]);
    }

    public function scores(Request $req)
    {
        $student = Student::where('id', session('std_id'))
            ->where('user_id', $req->user()->id)
            ->first();

        if (!$student) {
            session()->forget('std_id');
            return redirect('/student/login');
        }

        $courses = $student->courses()
            ->orderBy('course_name')
            ->get(['courses.id', 'courses.course_name', 'courses.class_name']);

        $courseId = (int) $req->query('course_id', $courses->first()->id ?? 0);
        $course = $courses->firstWhere('id', $courseId) ?? $courses->first();

        if (!$course) {
            return Inertia::render('student/Scores', [
                'courses' => [],
                'course' => null,
                'dates' => [],
                'selected_date' => Carbon::today()->toDateString(),
                'quizzes' => [],
                'daily_leaderboard' => [],
                'cumulative_leaderboard' => [],
                'student' => ['std_no' => $student->std_no, 'std_name' => $student->std_name],
            ]);
        }

        $students = $course->students()
            ->orderBy('std_no')
            ->get(['students.id', 'students.std_no', 'students.std_name']);

        $quizRows = DB::table('course_quizzes as quiz')
            ->join('questions as question', 'quiz.question_id', '=', 'question.id')
            ->where('quiz.course_id', $course->id)
            ->whereNull('quiz.deleted_at')
            ->orderBy('quiz.created_at')
            ->get([
                'quiz.id',
                'quiz.question_id',
                'quiz.marks',
                'quiz.created_at',
                'question.name as question_name',
                'question.question_type_id',
                'question.answer as expected_answer',
            ]);

        $dates = $quizRows
            ->map(fn ($quiz) => substr($quiz->created_at, 0, 10))
            ->unique()
            ->sortDesc()
            ->values();
        $requestedDate = $req->query('date');
        $today = Carbon::today()->toDateString();
        $selectedDate = is_string($requestedDate) && $dates->contains($requestedDate)
            ? $requestedDate
            : ($dates->contains($today) ? $today : ($dates->first() ?? $today));

        $quizIds = $quizRows->pluck('id');
        $attempts = $quizIds->isEmpty()
            ? collect()
            : DB::table('course_attempts')
                ->whereIn('course_quiz_id', $quizIds)
                ->whereIn('std_id', $students->pluck('id'))
                ->whereNull('deleted_at')
                ->get(['id', 'course_quiz_id', 'std_id']);

        $answersByAttempt = $attempts->isEmpty()
            ? collect()
            : DB::table('course_attempt_answers as answer')
                ->whereIn('answer.course_attempt_id', $attempts->pluck('id'))
                ->whereNull('answer.deleted_at')
                ->get([
                    'answer.course_attempt_id',
                    'answer.question_option_id',
                    'answer.answer as text_answer',
                ])
                ->groupBy('course_attempt_id');

        $correctOptionsByQuestion = $quizRows->isEmpty()
            ? collect()
            : DB::table('question_options')
                ->whereIn('question_id', $quizRows->pluck('question_id')->unique())
                ->where('is_correct', true)
                ->whereNull('deleted_at')
                ->get(['question_id', 'id'])
                ->groupBy('question_id')
                ->map(fn ($options) => $options->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all());

        $attemptsByQuiz = $attempts->groupBy('course_quiz_id');
        $scoresByQuiz = [];
        foreach ($quizRows as $quiz) {
            $quizAttempts = $attemptsByQuiz->get($quiz->id, collect())->keyBy('std_id');
            $fillInCorrectAttemptIds = [];
            if ((int) $quiz->question_type_id === 3 && $quizAttempts->isNotEmpty() && $quiz->expected_answer) {
                $fillInCorrectAttemptIds = DB::table('course_attempt_answers')
                    ->whereIn('course_attempt_id', $quizAttempts->pluck('id'))
                    ->whereNull('deleted_at')
                    ->where('answer', 'regexp', $quiz->expected_answer)
                    ->pluck('course_attempt_id')
                    ->all();
            }

            foreach ($students as $classmate) {
                $attempt = $quizAttempts->get($classmate->id);
                $isCorrect = false;
                if ($attempt) {
                    $answers = $answersByAttempt->get($attempt->id, collect());
                    if ((int) $quiz->question_type_id === 3) {
                        $isCorrect = in_array($attempt->id, $fillInCorrectAttemptIds);
                    } else {
                        $selectedOptionIds = $answers->pluck('question_option_id')
                            ->filter()
                            ->map(fn ($id) => (int) $id)
                            ->unique()
                            ->sort()
                            ->values()
                            ->all();
                        $correctOptionIds = $correctOptionsByQuestion->get($quiz->question_id, []);
                        $isCorrect = $correctOptionIds !== [] && $selectedOptionIds === $correctOptionIds;
                    }
                }
                $scoresByQuiz[$quiz->id][$classmate->id] = [
                    'score' => $isCorrect ? (float) $quiz->marks : 0,
                    'attempted' => (bool) $attempt,
                ];
            }
        }

        $rankLeaderboard = function (array $totals) use ($students, $student) {
            return $students->map(function ($classmate) use ($totals, $student) {
                $score = (float) ($totals[$classmate->id] ?? 0);

                return [
                    'student_id' => $classmate->id,
                    'std_no' => $classmate->std_no,
                    'std_name' => $classmate->std_name,
                    'score' => $score,
                    'rank' => collect($totals)->filter(fn ($total) => $total > $score)->count() + 1,
                    'is_me' => (int) $classmate->id === (int) $student->id,
                ];
            })->sortBy('rank')->values();
        };

        $dailyQuizRows = $quizRows->filter(fn ($quiz) => substr($quiz->created_at, 0, 10) === $selectedDate)->values();
        $dailyTotals = $students->mapWithKeys(fn ($classmate) => [$classmate->id => 0.0])->all();
        foreach ($dailyQuizRows as $quiz) {
            foreach ($students as $classmate) {
                $dailyTotals[$classmate->id] += $scoresByQuiz[$quiz->id][$classmate->id]['score'] ?? 0;
            }
        }

        $cumulativeTotals = $students->mapWithKeys(fn ($classmate) => [$classmate->id => 0.0])->all();
        foreach ($quizRows as $quiz) {
            foreach ($students as $classmate) {
                $cumulativeTotals[$classmate->id] += $scoresByQuiz[$quiz->id][$classmate->id]['score'] ?? 0;
            }
        }

        $quizzes = $dailyQuizRows->map(function ($quiz) use ($scoresByQuiz, $students, $student) {
            $quizTotals = $students->mapWithKeys(fn ($classmate) => [
                $classmate->id => $scoresByQuiz[$quiz->id][$classmate->id]['score'] ?? 0,
            ])->all();
            $leaderboard = collect($quizTotals)->sortDesc()->values();
            $myScore = (float) ($quizTotals[$student->id] ?? 0);

            return [
                'id' => $quiz->id,
                'question_name' => $quiz->question_name,
                'time' => substr($quiz->created_at, 11, 5),
                'marks' => (float) $quiz->marks,
                'score' => $myScore,
                'rank' => $leaderboard->filter(fn ($score) => $score > $myScore)->count() + 1,
                'attempted' => $scoresByQuiz[$quiz->id][$student->id]['attempted'] ?? false,
            ];
        });

        return Inertia::render('student/Scores', [
            'courses' => $courses->map(fn ($item) => [
                'id' => $item->id,
                'course_name' => $item->course_name,
                'class_name' => $item->class_name,
            ])->values(),
            'course' => [
                'id' => $course->id,
                'course_name' => $course->course_name,
                'class_name' => $course->class_name,
            ],
            'dates' => $dates,
            'selected_date' => $selectedDate,
            'quizzes' => $quizzes,
            'daily_leaderboard' => $rankLeaderboard($dailyTotals),
            'cumulative_leaderboard' => $rankLeaderboard($cumulativeTotals),
            'student' => ['std_no' => $student->std_no, 'std_name' => $student->std_name],
        ]);
    }

    private function hasSessionInfo(){
        return session()->has('std_id');
    }
}
