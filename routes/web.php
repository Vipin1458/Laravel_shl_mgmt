<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/test-validation', function (Illuminate\Http\Request $request) {
    $request->validate([
        'name' => 'required|string',
    ]);
    return 'Validation passed!';
});

