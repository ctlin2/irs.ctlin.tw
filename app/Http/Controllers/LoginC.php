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

class LoginC extends BaseController
{

    public function teacherLogin(Request $req){

        $e_msg=session('e_msg')??'';
        return Inertia::render('teacher/Login',
            [
                'e_msg'=>$e_msg
            ]);
    }


    public function teacherLoginPost(Request $req){
        Log::info('[enter teacherLoginPost] req='.json_encode($req->all()));
        if($req->has('t_account')&$req->has('t_password')){
            $t_account=$req->get('t_account');
            $t_password=$req->get('t_password');
            if($t_account=='ctlin'&&$t_password=='iI0/d138g/'){
                Log::info('[enter teacherLoginPost] 11 ');
                session(['teacher_login'=>1]);
                return redirect()->secure('teacher/course');

            }else{
                Log::info('[enter teacherLoginPost] 22 '.json_encode($req->all()));
                return redirect()->secure('teacher/login')->with('e_msg','帳號、密碼錯誤！');
            }
        }
    }



    public function studentLogin(Request $req){
        return Inertia::render('student/Login',
            [

            ]);
    }

    public function studentLoginPost(Request $req){
        if($this->checkStudentPermission($req)){
            session(['std_login'=>1]);
            session(['std_id'=>1]);
        }
    }

    private function checkStudentPermission(Request $req)
    {
        return true;
    }


}
