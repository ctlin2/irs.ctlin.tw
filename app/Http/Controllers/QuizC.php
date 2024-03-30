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
        if (!$this->hasSessionInfo()) {
            return redirect('teacher/course');
        }
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
//        Log::info('addQuestion'.json_encode($req->all()));
        $vue_q_obj=$req->get('question');
        $q_name=$vue_q_obj['name'];
        $q_type_id=$vue_q_obj['question_type_id'];
        $q_topic_id=$vue_q_obj['topic_id'];
        $q_rd=Question::create([
            'name'=>$q_name,
            'question_type_id'=>$q_type_id,
            'is_active'=>1,
        ]);
        $q_options= $req->get('q_options');
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
        $course_date = session('course_date');

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

        $course_quiz_rds = Course_quiz::from('course_quizzes as t1')
            ->join('questions as t2', 't1.question_id', '=', 't2.id')
            ->where('t1.course_id', $course_id)
            ->where('t1.course_date', $course_date)
            ->select('t1.*', 't2.name')->get();
        $report=null;
        foreach($course_quiz_rds as $course_quiz_rd){
            $quiz_id=$course_quiz_rd->id;
            $get_report_data = DB::select("CALL quiz_report(?)", array($quiz_id))[0];
            $report[$quiz_id]['std_answer_num']=intval($get_report_data->total_count) ?? 0;
            $report[$quiz_id]['std_answer_correct_num']=intval($get_report_data->is_correct_1_count) ?? 0;
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
                    'course_date'=>$course_date
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
                                    ->select('course_attempts.*', 'students.std_no', 'students.std_name')
                                    ->get();

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




    private function hasSessionInfo(){
        return session()->has('course_id');
    }



}
