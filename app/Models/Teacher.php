<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',                // ✅ add this to link with users table
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'subject_specialization',
        'employee_id',
        'date_of_joining',
        'status',
    ];

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class); // ✅ relation to user login
    }
}
