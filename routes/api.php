<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']); // Only for first admin setup
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth.jwt'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// ✅ Admin CRUD
Route::group(['middleware' => ['auth.jwt', 'role:admin']], function () {
    Route::apiResource('teachers', TeacherController::class);
    Route::apiResource('students', StudentController::class); // Admin manages ALL students
});

// ✅ Teacher CRUD (only their own students)
Route::group(['middleware' => ['auth.jwt', 'role:teacher']], function () {
    // Teacher uses the SAME StudentController but will be restricted automatically
    Route::apiResource('students', StudentController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
});
