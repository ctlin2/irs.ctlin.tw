<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course_attempt_answer extends Model
{
    protected $primaryKey = 'id';
    protected $guarded = ['id'];

    public $timestamps = false;
}
