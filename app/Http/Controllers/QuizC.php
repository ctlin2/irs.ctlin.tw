<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use DB;
use Log;
use Carbon\Carbon;

use App\Models\Topic;
use App\Models\Question;
use App\Models\Topicable;
use App\Models\Student;
use App\Models\Course_quiz;
use App\Models\Course_attempt;
use App\Models\QuestionOption;
use App\Models\S_point;

class QuizC extends BaseController
{

    public function topic(Request $req){
        $topics=DB::select("CALL get_all_topic()");
        if($req->has('json')) {
            return response()->json(['topics' => $topics]);
        }else{
            return Inertia::render('teacher/Topic',
                [
                    'topics' => $topics,
                ]);
        }
    }


    public function topicPost(Request $req)
    {
        $action = $req->get('_action');
        // if (!$this->hasSessionInfo()) {
        //     return redirect('teacher/course');
        // }
        $course_id = session('course_id');
        $course_date = session('course_date');

        switch ($action) {
            case 'add_topic':
                $this->addTopic($req);
                break;
            case 'change_topic':
                $this->changeTopic($req);
                break;
            case 'del_topic':
                $this->delTopic($req);
                break;
        }
    }

    private function addTopic(Request $req){
        $t_name=$req->get('t_name');
        $parent_id = $req->get('parent_id');
        Topic::create([
            'name'=>$t_name,
            'slug'=>'',
            'parent_id'=>$parent_id,
        ]);
    }

    private function changeTopic(Request $req){
        $data =$req->get('my_data');
        $t_id=$data['id'];
        Topic::find($t_id)->update($data);
    }

    private function delTopic(Request $req){
        $t_id=$req->get('id');
        Topic::find($t_id)->delete();
    }

    public function question(Request $req){
        $question_ids=[];
        $questions=[];
        if($req->has('t_id')){
            $t_id = $req->get('t_id');
            $get_child_topic_ids = DB::select("CALL get_child_topic_ids(?)", array($t_id));

            // jin-mo modify
            $childTopicIds = collect($get_child_topic_ids)->pluck('id')->all();
            $question_ids = Topicable::whereIn('topic_id', $childTopicIds)->where('topicable_type', 'questions')->pluck('topicable_id')->all();
            $questions = Question::whereIn('id', $question_ids)->get();

        }else{
            // jin-mo modify
            $topicable_ids=Topicable::where('topicable_type', 'questions')->pluck('topicable_id')->all();
            $questions=Question::whereNotIn('id',$topicable_ids)->get();
        }
        foreach($questions as $question){
            $topicable_rd=Topicable::where('topicable_id',$question->id)->where('topicable_type','questions')->get()->first();
            if($topicable_rd){
                $question->topic_id=$topicable_rd->topic_id;
            }else{
                $question->topic_id=null;
            }
        }
        $q_ids=$questions->pluck('id')->all();
        $q_options=QuestionOption::whereIn('question_id',$q_ids)->get();

        $topics=DB::select("CALL get_all_topic()");
        if($req->has('json')) {
            return response()->json(['questions' => $questions,
                'q_options'=>$q_options,
                'topics'=> $topics
            ]);
        }else{
            return Inertia::render('teacher/Question',
                [
                    'questions' => $questions,
                    'topic'=>$topics,
                    'q_options'=>$q_options
                ]);
        }

    }

    public function questionPost(Request $req){
        $action = $req->get('_action');
//        if (!$this->hasSessionInfo()) {
//            return redirect('teacher/course');
//        }
        $course_id = session('course_id');
        $course_date = session('course_date');

        switch ($action) {
            case 'add_question':
                $this->addQuestion($req);
                break;
            case 'change_question':
                $this->changeQuestion($req);
                break;
            case 'del_question':
                $this->delQuestion($req);
                break;
        }
    }

    private function addQuestion(Request $req){
       Log::info('addQuestion'.json_encode($req->all()));
        $vue_q_obj=$req->get('question');
        $q_name=$vue_q_obj['name'];
        $q_type_id=$vue_q_obj['question_type_id'];
        $q_topic_id=$vue_q_obj['topic_id'];
        $q_options= $req->get('q_options');

        // added by C.T.Lin to determinate multiple answers.
        $len_correct = 0;
        foreach($q_options as $q_option){
            if ($q_option['is_correct'] !== false){
                ++$len_correct;
            };
        }
        $q_rd=Question::create([
            'name'=>$q_name,
            'question_type_id'=>$len_correct > 1 ? 2 : $q_type_id, // modified by C.T.Lin
            'is_active'=>1,
        ]);
        
        foreach($q_options as $q_option){
            QuestionOption::create([
               'question_id'=>$q_rd->id,
                'name'=>$q_option['name'],
                'is_correct'=>$q_option['is_correct'],
            ]);
        }
        // jin-mo modify
        if (!is_null($q_topic_id)) {
            Topicable::create([
                'topicable_type'=>'questions',
                'topicable_id'=>$q_rd->id,
                'topic_id'=>$q_topic_id,
            ]);
        }
    }

    private function changeQuestion(Request $req){
        Log::info("Changing Question ".json_encode($req->all()));
        // jin-mo modify
        $question = $req->get('question');
        $q_options = $req->get('q_options');
        $del_options = $req->get('del_options');

        // step 1 del option
        if (count($del_options) != 0) {
            QuestionOption::whereIn('id', $del_options)->delete();
        }

        // step 2 update options
        foreach ($q_options as $item) {
            if (is_null($item['id'])) {
                QuestionOption::create([
                    'question_id' => $question['id'],
                    'name' => $item['name'],
                    'is_correct' => $item['is_correct']
                ]);
            } else {
                QuestionOption::find($item['id'])->update(['name' => $item['name'], 'is_correct' => $item['is_correct']]);
            }
        }

        // step 3 update question
        if($question['id']==null){
            $vue_q_obj=$req->get('question');
            $q_name=$vue_q_obj['name'];
            $q_type_id=$vue_q_obj['question_type_id'];
            $q_rd=Question::create(
                [
                'name'=>$q_name,
                'question_type_id'=>$q_type_id,
                'is_active'=>1,
                ]
            );
        }else{
            $q_rd=Question::find($question['id']);
            $q_rd->update(['name' => $question['name']]);
        }

        Log::info("q_rd= ".json_encode($q_rd));

//        $q_rd->update(['name' => $question['name']]);
//        Question::find($question['id'])->update(['name' => $question['name']]);

        // step 4 update topicable
        if (is_null($question['topic_id'])) {
//            Topicable::where('topicable_id', $question['id'])->where('topicable_type', 'questions')->delete();
            Topicable::where('topicable_id', $q_rd->id)->where('topicable_type', 'questions')->delete();
        } else {
            Topicable::updateOrCreate(
                ['topicable_id' => $q_rd->id, 'topicable_type' => 'questions'],
                ['topic_id' => $question['topic_id']]
            );
        }
    }


    private function delQuestion(Request $req){
        // jin-mo modify
        $q_id = $req->get('q_id');
        Question::find($q_id)->delete();
        QuestionOption::where('question_id', $q_id)->delete();
        Topicable::where('topicable_id', $q_id)->where('topicable_type', 'questions')->delete();
    }


    public function quiz(Request $req){
        if (!$this->hasSessionInfo()) {
            return redirect('teacher/course');
        }

//        if(!$req->has('course_date')||!$req->has('course_id')){
//            return redirect('teacher/course');
//        }
        $course_id = session('course_id');
        $course_date = session('course_date'); // useless

        $question_ids=[];
        $questions=[];
        if($req->has('t_id')){
            $t_id = $req->get('t_id');
            $get_child_topic_ids = DB::select("CALL get_child_topic_ids(?)", array($t_id));

            // jin-mo modify
            $childTopicIds = collect($get_child_topic_ids)->pluck('id')->all();
            $question_ids = Topicable::whereIn('topic_id', $childTopicIds)->where('topicable_type', 'questions')->pluck('topicable_id')->all();
            $questions = Question::whereIn('id', $question_ids)->get();

        }else{
            // jin-mo modify
            $topicable_ids=Topicable::where('topicable_type', 'questions')->pluck('topicable_id')->all();
            $questions=Question::whereNotIn('id',$topicable_ids)->get();
        }

        foreach($questions as $question){
            $topicable_rd=Topicable::where('topicable_id',$question->id)->where('topicable_type','questions')->get()->first();
            if($topicable_rd){
                $question->topic_id=$topicable_rd->topic_id;
            }else{
                $question->topic_id=null;
            }
        }
        $q_ids=$questions->pluck('id')->all();
        $q_options=QuestionOption::whereIn('question_id',$q_ids)->get(['id','question_id','name']);

        $topics=DB::select("CALL get_all_topic()");

        // find all the quizzes today
        $course_quiz_rds = Course_quiz::from('course_quizzes as t1')
            ->join('questions as t2', 't1.question_id', '=', 't2.id')
            ->where('t1.course_id', $course_id)
            // ->where('t1.expired_at', '>', Carbon::now()->subDays(1)->format('Y-m-d H:i:s')) // modified by C.T.Lin
            ->where('t1.expired_at', '>', Carbon::today()->format('Y-m-d H:i:s')) // modified by C.T.Lin
            ->select('t1.*', 't2.name')->get();
    
        // for each quiz, summarize how many students have answered and how many of them answer correctly.
        $report=null;
        foreach($course_quiz_rds as $course_quiz_rd){
            $quiz_id=$course_quiz_rd->id;
            $no_attempts = Course_attempt::where('course_quiz_id', $quiz_id)->count();
            $get_report_data = DB::select("CALL quiz_report2(?)", array($quiz_id))[0];

            $report[$quiz_id]['std_answer_num']=intval($no_attempts) ?? 0;
            $report[$quiz_id]['std_answer_correct_num']=intval($get_report_data->correct_count) ?? 0;
        }

        if($req->has('json')) {
            return response()->json([
                'questions' => $questions,
                'q_options'=>$q_options,
                'topics'=> $topics,
                'course_quiz_rds'=>$course_quiz_rds,
                'report'=>$report
            ]);
        }else{
            return Inertia::render('teacher/Quiz',
                [
                    'questions' => $questions,
                    'topic'=>$topics,
                    'q_options'=>$q_options,
                    'course_quiz_rds'=>$course_quiz_rds,
                    'report'=>$report,
                    'course_id'=>$course_id,
                    'course_date'=>$course_date // not needed. quiz alwasys been done today.
                ]);
        }
    }

    public function quizAnswerDetail(Request $req){
        $course_quiz_id = $req->get('course_quiz_id');

        $quiz_rd = Course_quiz::find($course_quiz_id);
//         $students = Student::where('course_id', $quiz_rd->course_id)->get();
        $q_options = QuestionOption::where('question_id', $quiz_rd->question_id)->get();
//         $answers = Course_attempt::where('course_quiz_id', $course_quiz_id)->get();
        $answers = Course_attempt::where('course_quiz_id', $course_quiz_id)
                                    ->join('students', 'course_attempts.std_id', '=', 'students.id')
                                    ->join('course_attempt_answers', 'course_attempt_answers.course_attempt_id', '=', 'course_attempts.id')
                                    ->select('course_attempt_answers.course_attempt_id', // added by C.T.Lin
                                        'course_attempt_answers.question_option_id', 
                                        'course_attempts.std_id', // added by C.T.Lin
                                        'students.std_no', 'students.std_name')
                                    ->orderby('course_attempt_answers.created_at')
                                    ->get();
        // ToDo : fill-in question

        if($req->has('json')) {
            return response()->json([
                'quiz_rd' => $quiz_rd,
                'std_answers' => $answers,
                'q_options' => $q_options
            ]);
        } else {
            return Inertia::render('teacher/QuizAnswerDetail', [
                'std_answers' => $answers,
                'q_options' => $q_options
            ]);
        }
    }


    public function quizAnswerDetailPost(Request $req){ // added by C.T.Lin
        $action = $req->get('_action');
        $course_id = session('course_id');
        // $course_date = session('course_date');

        switch ($action) {
            case 'add_student_points':
                $this->addStudentPoints($req);
                break;
            case 'add_attendant':
                $this->addAttendant($req);
                break;
            case 'add_absent':
                $this->addAbsent($req);
                break;
        }
    }
    

    public function quizPost(Request $req){

        $question_id=$req->get('question_id');

        $action = $req->get('_action');
        if (!$this->hasSessionInfo()) {
            return redirect('teacher/course');
        }
        $course_id = session('course_id');
        $course_date = session('course_date');

        switch ($action) {
            case 'add_quiz':
                $this->addQuiz($req,$course_id,$course_date);
                break;
            case 'del_quiz':
                $this->delQuiz($req,$course_id);
                break;
        }
    }


    private function addQuiz(Request $req,$course_id,$course_date){
        // teacher add a quiz
        // course_id,course_date , topic_id
        // if there are course_id & course_date then get the quiz
        // chose a question assign to quiz_questions

        if ($req->get('question_id') == null)
            return redirect('teacher/quiz');

        $expired_time_min=$req->get('expired_time')??5;
        $created_at= Carbon::now();
        $expired_at =  $created_at->copy()->addMinutes($expired_time_min);

        Course_quiz::create([
           'course_id' => $course_id,
            'course_date' => $course_date,
            'question_id'=>$req->get('question_id'),
            'created_at' => $created_at,
            'expired_at' => $expired_at
        ]);

    }

    private function delQuiz(Request $req){
        $quiz_id= $req->get('quiz_id');
        Course_attempt::where('course_quiz_id',$quiz_id)->delete();
        Course_quiz::find($quiz_id)->delete();
    }

    private function chooseAQuestion(int $topic_id){
        $questions=DB::select("CALL get_all_question(?)",array($topic_id));
    }

    private function addStudentPoints(Request $req){
        $student_answers = $req->get('student_answers');
        $course_attempt = Course_attempt::find($student_answers[0]['course_attempt_id']);
        $sql = <<<EOD
        select course_attempts.id as course_attempt_id, std_id
        from course_attempts  
        join course_quizzes
        on course_attempts.course_quiz_id = course_quizzes.id
        where course_quizzes.id = :in_course_quiz_id
        and 
        course_attempts.id not in (
        (
            select course_attempts.id 
            from course_attempt_answers
            join question_options
            on course_attempt_answers.question_option_id = question_options.id
            join course_quizzes
            on course_quizzes.question_id = question_options.question_id
            join course_attempts 
            on course_attempts.id = course_attempt_answers.course_attempt_id
            where question_options.is_correct = 0
        ) 
        union 
        (
            select course_attempts.id
            from question_options
            join course_quizzes
            on question_options.question_id = course_quizzes.question_id
            join course_attempts
            on course_attempts.course_quiz_id = course_quizzes.id
            where question_options.is_correct = 1
            and question_options.id NOT IN (
            select question_option_id 
            from course_attempt_answers
            join course_attempts
            on course_attempts.id = course_attempt_answers.course_attempt_id)
        ))
        EOD;
        $correct_attempts = DB::select($sql, array('in_course_quiz_id'=>$course_attempt->course_quiz_id));
        // dd($correct_attempts);
        $mark = 5;
        foreach ($correct_attempts as $c_attempt){
            S_point::where('std_id', '=', $c_attempt->std_id)->increment('s_point', $mark);
        }
    }

    private function addAttendant(Request $req){
        $student_id_array = $req->get('student_ids');
        $course_attempt_id = $req->get('course_attempt_id');

        $course_attempt = Course_attempt::find($course_attempt_id);
        $course_quiz = Course_quiz::find($course_attempt->course_quiz_id);

        // get students from s_points for today course
        $s_points = S_point::where('course_id', '=', $course_quiz->course_id)
        ->where('course_date', '=', Carbon::today()->format('Y-m-d'))
        ->get();

        foreach($s_points as $s_point){
            if (in_array( $s_point->std_id , $student_id_array, true)){
                $s_point->update(['status' => 0]); // 出席
            }
        }
    }

    private function addAbsent(Request $req){
        $student_id_array = $req->get('student_ids');
        $course_attempt_id = $req->get('course_attempt_id');

        $course_attempt = Course_attempt::find($course_attempt_id);
        $course_quiz = Course_quiz::find($course_attempt->course_quiz_id);
        
        // get students from s_points for today course
        $s_points = S_point::where('course_id', '=', $course_quiz->course_id)
        ->where('course_date', '=', Carbon::today()->format('Y-m-d'))
        ->get();

        foreach($s_points as $s_point){
            if (!in_array( $s_point->std_id , $student_id_array, true)){
                $s_point->update(['status' => 4]); // 缺席
            }
        }
    }

    private function addLeave(Request $req){
        $student_id_array = $req->get('student_ids');
        $course_attempt_id = $req->get('course_attempt_id');

        $course_attempt = Course_attempt::find($course_attempt_id);
        $course_quiz = Course_quiz::find($course_attempt->course_quiz_id);
        
        // get students from s_points for today course
        $s_points = S_point::where('course_id', '=', $course_quiz->course_id)
        ->where('course_date', '=', Carbon::today()->format('Y-m-d'))
        ->get();

        foreach($s_points as $s_point){
            if (!in_array( $s_point->std_id , $student_id_array, true)){
                if ($s_point->status == 0) $s_point->update(['status' => 2]); // 出席=>早退
                if ($s_point->status == 1) $s_point->update(['status' => 3]); // 遲到=>遲到&早退
            }
        }
    }


    private function hasSessionInfo(){
        return session()->has('course_id');
    }



}
