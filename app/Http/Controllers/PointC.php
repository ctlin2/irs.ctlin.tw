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
use Carbon\Carbon;
use DB;

class PointC extends BaseController
{

    private function hasSessionInfo(){
        return session()->has('course_id');
    }

    public function pointBoard(Request $req){

        if(!$this->hasSessionInfo()){
            return redirect('teacher/course');
        }
        $course_id = $req->get('course_id')?? session('course_id');
        // $course_date=Carbon::today()->format('Y-m-d');
        $course_date = $req->get('course_date')?? session('course_date');

        $course = Course::find($course_id);
        // $students = Student::where('course_id', $course_id)->get(); // obsolete
        $students = $course->students;
        $s_points = S_point::from('s_points as t1')
            ->join('students as t2', 't1.std_id', '=', 't2.id')
            ->where('t1.course_id', $course_id)
            ->where('t1.course_date', $course_date)
            ->select('t1.*', 't2.std_no', 't2.std_name')
            ->orderBy('t1.s_point', 'DESC') 
            ->get();

        // $g_points = G_point::from('g_points as t1')
        //     ->join('groups as t2', 't1.group_id', '=', 't2.id')
        //     ->where('t1.course_id', $course_id)
        //     ->where('t1.course_date', $course_date)
        //     ->select('t1.*', 't2.no')
        //     ->orderBy('t1.g_point', 'DESC')
        //     ->get();
        
        $g_points = DB::table('g_points')
        ->join('s_points', 'g_points.group_id', '=', 's_points.group_id')
        ->join('groups', 'g_points.group_id', '=', 'groups.id')
        ->where('s_points.course_id', '=', $course_id)
        ->where('g_points.course_date', '=', $course_date)
        ->where('s_points.course_date', '=', $course_date)
        ->groupBy('g_points.group_id', 'groups.no', 'g_points.g_point')
        ->select(DB::raw('groups.no, (avg(s_point) + g_points.g_point) as g_point'))
        ->orderBy('g_point', 'desc')
        ->get();

        if($req->has('json')) {
            return response()->json(['g_points' => $g_points]);
        }

        $course_date_list=S_point::distinct()->pluck('course_date');
        if ($req->has('json')) {
            return response()->json(['course' => $course, 'students' => $s_points, 'groups' => $g_points,'course_date_list'=>$course_date_list]);
        } else {
            return Inertia::render('teacher/RankingList',
                [
                    'course' => $course,
                    'students' => $s_points,
                    'groups' => $g_points,
                    'course_date_list'=>$course_date_list,
                    'current_lookup_date' => $course_date, // added by C.T.Lin
                ]);
        }
    }

}
