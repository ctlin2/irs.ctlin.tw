<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


// php artisan make:migration add_path_to_audio_table --table=audio

class Student extends Model
{
    use SoftDeletes;
    //
//    protected $table = "audio";
    protected $primaryKey = 'id';
    protected $guarded = ['id'];


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

    /**
     * The courses that are taken by the student.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'takes', 'student_id', 'course_id'); 
    }
}
