<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // C.T.Lin
use App\Models\Take; // C.T.Lin
use App\Models\Teach; // C.T.Lin

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * 取得該使用者所教的課程。 C.T.Lin
     * Teacher one-to-many Teach(course_teacher), and Teach many-to-one Course. 
     */
    public function teach_courses(): BelongsToMany {
        // return $this->belongsToMany(Course::class, 'teacher', 'user_id', 'course_id');
        return $this->belongsToMany(Course::class, 'teaches');
    }

    /**
     * 取得該使用者所修習的課程。 C.T.Lin
     * Student one-to-many Take(course_student), and Take many-to-one Course. 
     */
    public function take_courses(): BelongsToMany {
        // return $this->belongsToMany(Role::class, 'takes', 'user_id', 'course_id');
        return $this->belongsToMany(Course::class, 'takes');
    }
}
