<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Eloquent\SoftDeletes;



// php artisan make:migration add_path_to_audio_table --table=audio

class Course_quiz extends Model
{
//    use SoftDeletes;
    //
//    protected $table = "course_quizs";
    protected $primaryKey = 'id';
    protected $guarded = ['id'];

    public $timestamps = false;
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
//        'password',
//        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
//        'email_verified_at' => 'datetime',
    ];
}
