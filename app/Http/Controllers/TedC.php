<?php

namespace App\Http\Controllers;

use App\Models\QuestionOption;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use DB;

use App\Models\Topic;
use App\Models\Question;
use App\Models\Topicable;

use Illuminate\Support\Facades\Storage;
//use Storage;
use File;

use App\Models\Student;
class TedC extends BaseController
{

    public function readJson(Request $req){

//        $abc['a']="123";
//        Storage::putFile('photos', new File('/photo'),'aaa',true);
//        Storage::put

        $path = Storage::path('file.jpg');

//        echo $path;

        $content = Storage::json('std1020.json');
//        $a = json_decode($content, true);
        $students=$content['students'];
        $groups=$content['groups'];
        $course_id=7;

        foreach($students as $student){
//            echo $student['name'];
            Student::updateOrCreate([
               'std_name' => $student['name'],
                'std_no'=>$student['id'],
                'course_id'=>$course_id
            ]);
        }
//        print($content['students']);
//        $students=$content['content']['students'];
//        print_r($students);ted
//        print_r($content);

        return response()->json([
           'content'=>$content,
//            'a'=>$a,
        ]);
    }


    public function ted(Request $req)
    {
        $topics = null;
        if ($req->has('t_id')) {
            $t_id = $req->get('t_id');
            $get_child_topic_ids = DB::select("CALL get_child_topic_ids(?)", array($t_id));
            $question_ids=[];
            foreach ($get_child_topic_ids as $child_topic_id) {
                $v = Topicable::where('topic_id', $child_topic_id->id)->pluck('topicable_id')->all();
                if(!empty($v)){
                    array_push($question_ids, $v);
                }
            }
            $questions = Question::whereIn('id', $question_ids)->get();
        } else {
            $topicable_ids = Topicable::pluck('topicable_id')->all();
            $q_ids = Question::pluck('id')->all();
            $arr = array();
            foreach ($q_ids as $k1 => $v1) {
                if (!in_array($v1, $topicable_ids)) {
                    array_push($arr, $v1);
                }
            }
            $questions = Question::whereIn('id', $arr)->get();
        }

        return response()->json([
            'topics' => $topics,
            'questions' => $questions

        ]);
    }



}
