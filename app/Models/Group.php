<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;



// php artisan make:migration add_path_to_audio_table --table=audio

class Group extends Model
{
//    protected $table = "audio";
    public $timestamps = false;
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
}
