<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course_attempt_answer extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $guarded = ['id'];

    public $timestamps = true;
}
