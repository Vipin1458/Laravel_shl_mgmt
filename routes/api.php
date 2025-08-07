<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']); 
Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']); 


Route::middleware(['auth.jwt'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/teacher/profile', [TeacherController::class, 'myProfile'])->middleware('role:teacher');
    Route::patch('/teacher/profile', [TeacherController::class, 'updateProfile'])->middleware('role:teacher');
    Route::get('/student/profile', [StudentController::class, 'myProfile'])->middleware('role:student');
    Route::patch('/student/profile', [StudentController::class, 'updateProfile'])->middleware('role:student');
});

Route::group(['middleware' => ['auth.jwt', 'role:admin']], function () {
    Route::apiResource('teachers', TeacherController::class);
    Route::apiResource('students', StudentController::class); 
    Route::get('/teachers/{teacherId}/students', [StudentController::class, 'studentsByTeacher']);
});

Route::group(['middleware' => ['auth.jwt', 'role:teacher']], function () {
    Route::apiResource('teacher-students', StudentController::class)
         ->only(['index', 'store', 'show', 'update', 'destroy']);
});
