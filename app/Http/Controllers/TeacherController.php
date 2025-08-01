<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{

    public function index()
    {
        return response()->json(Teacher::with('user')->get());
    }

    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name'            => 'required|string',
            'last_name'             => 'required|string',
            'email'                 => 'required|email|unique:users',
            'phone_number'          => 'required|string',
            'subject_specialization'=> 'required|string',
            'employee_id'           => 'required|string|unique:teachers',
            'date_of_joining'       => 'required|date',
            'status'                => 'required|in:Active,Inactive',
            'password'              => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name'     => $request->first_name . ' ' . $request->last_name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'teacher',
        ]);

        $teacher = Teacher::create([
            'user_id'               => $user->id,
            'first_name'            => $request->first_name,
            'last_name'             => $request->last_name,
            'email'                 => $request->email,
            'phone_number'          => $request->phone_number,
            'subject_specialization'=> $request->subject_specialization,
            'employee_id'           => $request->employee_id,
            'date_of_joining'       => $request->date_of_joining,
            'status'                => $request->status,
        ]);

        return response()->json(['message' => 'Teacher created successfully', 'teacher' => $teacher], 201);
    }

    public function show($id)
    {
        $teacher = Teacher::with('user')->findOrFail($id);
        return response()->json($teacher);
    }

  
    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);
        $user = User::findOrFail($teacher->user_id);

        $validator = Validator::make($request->all(), [
            'first_name'            => 'sometimes|required|string',
            'last_name'             => 'sometimes|required|string',
            'email'                 => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone_number'          => 'sometimes|required|string',
            'subject_specialization'=> 'sometimes|required|string',
            'employee_id'           => 'sometimes|required|string|unique:teachers,employee_id,' . $teacher->id,
            'date_of_joining'       => 'sometimes|required|date',
            'status'                => 'sometimes|required|in:Active,Inactive',
            'password'              => 'sometimes|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('first_name') || $request->has('last_name')) {
            $user->name = ($request->first_name ?? $teacher->first_name) . ' ' . ($request->last_name ?? $teacher->last_name);
        }
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

      
        $teacher->update($request->only([
            'first_name', 'last_name', 'email', 'phone_number',
            'subject_specialization', 'employee_id', 'date_of_joining', 'status'
        ]));

        return response()->json(['message' => 'Teacher updated successfully', 'teacher' => $teacher]);
    }

    
    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);
        $user = User::find($teacher->user_id);

        if ($user) {
            $user->delete(); 
        }

        $teacher->delete();

        return response()->json(['message' => 'Teacher deleted successfully']);
    }
}
