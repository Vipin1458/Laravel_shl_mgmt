<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']); 
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth.jwt'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::group(['middleware' => ['auth.jwt', 'role:admin']], function () {
    Route::apiResource('teachers', TeacherController::class);
    Route::apiResource('students', StudentController::class); 
});

Route::group(['middleware' => ['auth.jwt', 'role:teacher']], function () {
    Route::apiResource('teacher-students', StudentController::class)
         ->only(['index', 'store', 'show', 'update', 'destroy']);
});
