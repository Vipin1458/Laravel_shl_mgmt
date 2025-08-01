<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',       
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'roll_number',
        'class_grade',
        'date_of_birth',
        'admission_date',
        'status',
        'teacher_id',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function user()   
    {
        return $this->belongsTo(User::class);
    }
}
