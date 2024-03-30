<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\G_point;
use App\Models\S_point;
use App\Models\Student;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PointC extends BaseController
{


    public function pointBoard(Request $req){

        if (!$req->has('course_id')) {
            return redirect('teacher/course');
        }

        $course_id = $req->get('course_id');
        $course_date = $req->get('course_date');
//        session(['course_id' => $course_id, 'course_date' => $course_date]);

        $course = Course::find($course_id);
        $students = Student::where('course_id', $course_id)->get();
        $s_points = S_point::from('s_points as t1')
            ->join('students as t2', 't1.std_id', '=', 't2.id')
            ->where('t1.course_id', $course_id)
            ->where('t1.course_date', $course_date)
            ->select('t1.*', 't2.std_no', 't2.std_name')->get();

        $g_points = G_point::from('g_points as t1')
            ->join('groups as t2', 't1.group_id', '=', 't2.id')
            ->where('t1.course_id', $course_id)
            ->where('t1.course_date', $course_date)
            ->select('t1.*', 't2.no')
            ->get();

        $course_date_list=S_point::distinct()->pluck('course_date');
        if ($req->has('json')) {
            return response()->json(['course' => $course, 'students' => $s_points, 'groups' => $g_points,'course_date_list'=>$course_date_list]);
        } else {
            return Inertia::render('teacher/RankingList',
                [
                    'course' => $course,
                    'students' => $s_points,
                    'groups' => $g_points,
                   'course_date_list'=>$course_date_list
                ]);
        }



    }

}
