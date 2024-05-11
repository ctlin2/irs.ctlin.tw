<?php

namespace App\Http\Controllers;

use App\Models\G_point;
use App\Models\Group;
use App\Models\S_point;
use App\Models\Student;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Course;
use DB;

use App\Models\QuestionOption;
use Log;

class CourseC extends BaseController
{

    public function course(Request $req){
//        Log::info('course_test');
        $courses=Course::all();
        $students=DB::table('students as t1')->join('takes as t2', 't1.id', '=', 't2.student_id')
            ->select('t1.*', 't2.course_id')->orderBy('t1.std_no', 'ASC')->get(); // ToDo: student list depend on a course
        if($req->has('json')) {
            return response()->json(['course' => $courses]);
        }else{
            return Inertia::render('teacher/Course',
                [
                'courses' => $courses,
                'students'=>$students
            ]);
        }
    }


    public function homePost(Request $req){
        $action=$req->get('_action');
        if(!$this->hasSessionInfo()){
            return redirect('teacher/course');
        }
        $course_id=session('course_id');
        $course_date=session('course_date');

        switch($action){
            case 'add_group':
                $this->homePost_addGroup($req);
                break;
            case 'del_group':
                $this->homePost_delGroup($req);
                break;

            case 'add_member':
                $this->homePost_addMember($req);
                break;

            case 'del_member':
                $this->homePost_delMember($req);
                break;

            case 'change_group_point':
                $this->homePost_changeGPoint($req);
                break;

            case 'change_member_point':
                $this->homePost_changeMPoint($req);
                break;
            case 'change_member_status':
                $this->homePost_changeMStatus($req);
                break;

        }
    }

    private function homePost_changeMStatus(Request $req){
        S_point::find($req->get('s_point_id'))->update(['status' =>$req->get('status')]);
    }
    private function homePost_changeMPoint(Request $req){
        $s_point_id=$req->get('s_point_id');
        $s_point_value=$req->get('s_point_value');
        $s_point=S_point::find($s_point_id);
        if($s_point){
            $s_point->s_point=$s_point_value;
            $s_point->save();
        }
    }
    private function homePost_changeGPoint(Request $req){
        $g_point_id=$req->get('g_point_id');
        $g_point_value=$req->get('g_point_value');
        $g_point=G_point::find($g_point_id);
        if($g_point){
            $g_point->g_point=$g_point_value;
            $g_point->save();
        }
    }
    private function homePost_delMember(Request $req){
        $s_point_ids=$req->get('s_point_ids');
        $group_id=G_point::find($req->get('g_point_id'))->group_id;
        $s_point_ids=S_point::whereIn('id',$s_point_ids)->where('group_id',$group_id)->pluck('id');

        S_point::whereIn('id',$s_point_ids)->update(['group_id'=>null]);
        $student_ids=S_point::whereIn('id',$s_point_ids)->pluck('std_id');
        Student::whereIn('id',$student_ids)->update(['group_id'=>null]);

    }
    private function homePost_addMember(Request $req){

        $g_point_id=$req->get('g_point_id');
        $s_point_ids=$req->get('s_point_ids'); // array

        $g_point_rd=G_point::find($g_point_id);
        $group_id=$g_point_rd->group_id;

        $student_ids=S_point::whereIn('id',$s_point_ids)->pluck('std_id');
        Student::whereIn('id',$student_ids)->update(['group_id'=>$group_id]);
        S_point::whereIn('id',$s_point_ids)->update(['group_id'=>$group_id]);
    }

    private function homePost_delGroup(Request $req){
        // real delete data
        $g_point_id=$req->get('g_point_id');
        $g_point_rd=G_point::find($g_point_id);

        // the group_id is used to remember the recent group of a student.
        Student::where('group_id',$g_point_rd->group_id)
                // ->where('course_id',$g_point_rd->course_id)
                ->update(['group_id'=>null]);

        S_point::where('course_id',$g_point_rd->course_id)
            ->where('course_date',$g_point_rd->course_date)
            ->where('group_id',$g_point_rd->group_id)
            ->update(['group_id'=>null]);
        $group_id=$g_point_rd->group_id;
        $g_point_rd->delete(); // delete g_points entry

        // make sure useless in g_points
        if (G_point::where('group_id', $group_id)->count() == 0)
            Group::find($group_id)->delete(); // delete group entry only if no entry in g_points
    }

    private function hasSessionInfo(){
        return session()->has('course_id');
    }

    private function homePost_addGroup(Request $req){
        $course_id=session('course_id');
        $course_date=session('course_date');
        $rd_group=Group::create([
            'no'=>$req->get('no'),
            'course_id'=>$course_id
        ]);
        $rd_group->save();
        $rd_course=Course::find($course_id);
        G_point::create([
            'group_id'=>$rd_group->id,
            'course_id'=>$course_id,
            'course_date'=>$course_date,
            'g_point'=>$rd_course->def_g_point
        ]);
    }

    public function coursePost(Request $req){
        $action=$req->get('_action');
        switch($action){
            case 'add':
                Course::Create();
                break;
            case 'update':
                $this->coursePost_Update($req);
                break;
            case 'import_student':
                // std_xls
                return $this->coursePost_ImportStudent($req);
                break;
            case 'open_course':
                // require date, course_id
                $this->coursePost_OpenCourse($req);
//                $this->
        }
    }

    public function ted(Request $req){

        $rds=QuestionOption::all();

//        S_point::find(3)->update(['status'=>1]);

//        $student_ids=null;
//        $ary_ids=[2,3,4,5];
//        S_point::whereIn('id',$ary_ids)->update(['group_id'=>null]);
//        $student_ids=S_point::whereIn('id',$ary_ids)->pluck('std_id');
//        Student::whereIn('id',$student_ids)->update(['group_id'=>null]);
//        $rds->group_id=2;
//        $rds->save();

        return response()->json($rds);
    }

    /* ==============================================
        Retrieve students and groups data for 
        given: course_id and course_date
    ============================================== */
    private function coursePost_OpenCourse(Request $req){
        // generate s_point,g_point
        $course_id = $req->get('course_id');
        $course_date = $req->get('course_date');
        // $students=Student::where('course_id',$course_id)->get(); // obsolete (*important): a student may "takes" many courses. 
        // ToDo: convert the following code to a stored procedure

        $course_rd=Course::find($course_id);
        $students = $course_rd->students;  // students who take the course
        // dd($students);
        foreach($students as $student){
            // ToDo: retrieve the last status of the student?
            S_point::create([
                'std_id'=>$student->id,
                'course_id'=>$course_id,
                'course_date'=>$course_date,
                'group_id'=>$student->group_id,
                's_point'=>$course_rd->def_s_point
            ]);
        }
        $groups=Group::where('course_id',$course_id)->get();  // groups permanent or per date?
        foreach($groups as $group){
            G_point::create([
               'group_id'=>$group->id,
                'course_id'=>$course_id,
                'course_date'=>$course_date,
                'g_point'=>$course_rd->def_g_point
            ]);
        }

//
//        foreach($students as $stuent)


    }

    private function coursePost_ImportStudent(Request $req){
        //read xls
	    Log::info('req'.json_encode($req->all()));
        if($req->file('std_xls')){
            $course_id=$req->get('course_id');
            foreach($req->file('std_xls') as $key => $file){
            	$content=file_get_contents($file);
                $students=explode("\n",$content);
                Log::info('std num ='.count($students));
                foreach($students as $student){
                    $s_info=explode(',',$student);

                    Student::updateOrCreate(
                        [ 'std_no'=>$s_info[0] ],
                        [ 'std_name' => $s_info[1] ]
                    );

                    // update "takes"
                    // $course = Course::find('course_id', $course_id);
                    $rd_student = Student::where('std_no', $s_info[0])->first();
                    $rd_student->courses()->attach(Course::find($course_id));
                    // Updating pivot data
                    // $rd_student->courses()->updateExistingPivot($course->id, ['additional_data' => 'new_value']);
		        }
            }
//            return response()->json([200]);
        }
//        return response()->json([501]);
    }
    private function coursePost_Update(Request $req){
        $data=$req->get('my_data');
        $rd=Course::find($data['id']);
        $rd->fill($data);
        $rd->save();
    }


    private function updateProductsWithChangedData(Request $req){
        $product_ids=$req->get('product_ids');
        $changed_data=$req->get('changed_data');
        $num = count($product_ids);
//        Log::info('num ='.$num);

        if(count($product_ids) > 0){
            Yjs_products::whereIn('product_id',$product_ids)->update($changed_data);
        }
    }


    public function home(Request $req)
    {
        if (!$req->has('course_id')) {
            return redirect('teacher/course');
        }

        $course_id = $req->get('course_id');
        $course_date = $req->get('course_date');
        session(['course_id' => $course_id, 'course_date' => $course_date]);

        $course = Course::find($course_id);
        // $students = Student::where('course_id', $course_id)->get(); // obsolete
        $students = $course->students;
        
        // find existing entries
        $s_points = S_point::from('s_points as t1')
            ->join('students as t2', 't1.std_id', '=', 't2.id')
            ->where('t1.course_id', $course_id)
            ->where('t1.course_date', $course_date)
            ->orderBy('t2.std_no', 'ASC')
            ->select('t1.*', 't2.std_no', 't2.std_name');

        if ($s_points->count() == 0) {
            // call insert data
            $this->coursePost_OpenCourse($req);
        }
        $s_points = $s_points->get();

        $g_points = G_point::from('g_points as t1')
            ->join('groups as t2', 't1.group_id', '=', 't2.id')
            ->where('t1.course_id', $course_id)
            ->where('t1.course_date', $course_date)
            ->select('t1.*', 't2.no')
            ->get();
        if ($req->has('json')) {
            return response()->json(['course' => $course, 'students' => $s_points, 'groups' => $g_points]);
        } else {
            return Inertia::render('teacher/Home',
                [
                    'course_date' => $course_date,
                    'course' => $course,
                    'students' => $s_points,
                    'groups' => $g_points
                ]);
        }
    }

}
