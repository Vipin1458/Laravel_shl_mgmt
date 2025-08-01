<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
   
    public function index()
    {
        $authUser = auth()->user();

        if ($authUser->role === 'teacher') {
            $teacherId = Teacher::where('user_id', $authUser->id)->value('id');
            return response()->json(Student::where('teacher_id', $teacherId)->get());
        }

        return response()->json(Student::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name'      => 'required|string',
            'last_name'       => 'required|string',
            'email'           => 'required|email|unique:users',
            'phone_number'    => 'required|string',
            'roll_number'     => 'required|string|unique:students',
            'class'           => 'required|string',
            'date_of_birth'   => 'required|date',
            'admission_date'  => 'required|date',
            'status'          => 'required|in:Active,Inactive',
            'password'        => 'required|min:6',
            'assigned_teacher'=> 'nullable|exists:teachers,id'
        ]);

        $authUser = auth()->user();

        $user = User::create([
            'name'     => $request->first_name . ' ' . $request->last_name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'student',
        ]);

        $teacherId = $request->assigned_teacher;

        if ($authUser->role === 'teacher') {
            $teacherId = Teacher::where('user_id', $authUser->id)->value('id');
        }

        $student = Student::create([
            'user_id'       => $user->id,
            'teacher_id'    => $teacherId,
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'email'         => $request->email,
            'phone_number'  => $request->phone_number,
            'roll_number'   => $request->roll_number,
            'class'         => $request->class,
            'date_of_birth' => $request->date_of_birth,
            'admission_date'=> $request->admission_date,
            'status'        => $request->status,
        ]);

        return response()->json(['message' => 'Student created successfully', 'student' => $student], 201);
    }

  
    public function show($id)
    {
        $student = Student::findOrFail($id);

        $authUser = auth()->user();

        if ($authUser->role === 'teacher') {
            $teacherId = Teacher::where('user_id', $authUser->id)->value('id');

            if ($student->teacher_id !== $teacherId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        return response()->json($student);
    }

    
    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $user = User::findOrFail($student->user_id);

        $authUser = auth()->user();

        if ($authUser->role === 'teacher') {
            $teacherId = Teacher::where('user_id', $authUser->id)->value('id');

            if ($student->teacher_id !== $teacherId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        $request->validate([
            'first_name'      => 'sometimes|required|string',
            'last_name'       => 'sometimes|required|string',
            'email'           => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone_number'    => 'sometimes|required|string',
            'roll_number'     => 'sometimes|required|string|unique:students,roll_number,' . $student->id,
            'class'           => 'sometimes|required|string',
            'date_of_birth'   => 'sometimes|required|date',
            'admission_date'  => 'sometimes|required|date',
            'status'          => 'sometimes|required|in:Active,Inactive',
        ]);

        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('first_name') || $request->has('last_name')) {
            $user->name = ($request->first_name ?? $student->first_name) . ' ' . ($request->last_name ?? $student->last_name);
        }
        $user->save();

        $student->update($request->only([
            'first_name', 'last_name', 'email', 'phone_number', 'roll_number',
            'class', 'date_of_birth', 'admission_date', 'status'
        ]));

        return response()->json(['message' => 'Student updated successfully', 'student' => $student]);
    }

    
    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $authUser = auth()->user();

        if ($authUser->role === 'teacher') {
            $teacherId = Teacher::where('user_id', $authUser->id)->value('id');

            if ($student->teacher_id !== $teacherId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        $user = User::find($student->user_id);
        if ($user) {
            $user->delete();
        }

        $student->delete();

        return response()->json(['message' => 'Student deleted successfully']);
    }
}
