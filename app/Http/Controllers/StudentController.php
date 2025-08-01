<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
   
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'teacher' || $request->is('api/teacher-students*')) {
            $teacherId = Teacher::where('user_id', $user->id)->value('id');
            return response()->json(Student::where('teacher_id', $teacherId)->with('user')->get());
        }

        return response()->json(Student::with('teacher', 'user')->get());
    }

    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'         => 'required|email|unique:users',
            'password'      => 'required|min:6',
            'first_name'    => 'required|string',
            'last_name'     => 'required|string',
            'phone_number'  => 'required|string',
            'roll_number'   => 'required|string|unique:students',
            'class_grade'   => 'required|string',
            'date_of_birth' => 'required|date',
            'admission_date'=> 'required|date',
            'status'        => 'required|in:Active,Inactive',
            'teacher_id'    => 'nullable|exists:teachers,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loggedUser = Auth::user();

        if ($loggedUser->role === 'teacher' || $request->is('api/teacher-students*')) {
            $teacherId = Teacher::where('user_id', $loggedUser->id)->value('id');
        } else {
            $teacherId = $request->teacher_id;
        }

        $user = User::create([
            'name'     => $request->first_name . ' ' . $request->last_name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'student',
        ]);

        $student = Student::create([
            'user_id'       => $user->id,
            'teacher_id'    => $teacherId,
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'email'         => $request->email,
            'phone_number'  => $request->phone_number,
            'roll_number'   => $request->roll_number,
            'class_grade'   => $request->class_grade,
            'date_of_birth' => $request->date_of_birth,
            'admission_date'=> $request->admission_date,
            'status'        => $request->status,
        ]);

        return response()->json(['message' => '✅ Student created successfully', 'student' => $student], 201);
    }

   
    public function show(Request $request, $id)
    {
        $student = Student::with('teacher', 'user')->find($id);

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $loggedUser = Auth::user();

        if (($loggedUser->role === 'teacher' || $request->is('api/teacher-students*')) &&
            $student->teacher_id !== Teacher::where('user_id', $loggedUser->id)->value('id')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($student);
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $user = User::findOrFail($student->user_id);
        $loggedUser = Auth::user();

        if (($loggedUser->role === 'teacher' || $request->is('api/teacher-students*')) &&
            $student->teacher_id !== Teacher::where('user_id', $loggedUser->id)->value('id')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'email'       => 'sometimes|required|email|unique:users,email,' . $user->id,
            'roll_number' => 'sometimes|required|string|unique:students,roll_number,' . $student->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('first_name') || $request->has('last_name')) {
            $user->name = ($request->first_name ?? $student->first_name) . ' ' .
                          ($request->last_name ?? $student->last_name);
        }
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        $student->update($request->only([
            'first_name', 'last_name', 'email', 'phone_number',
            'roll_number', 'class_grade', 'date_of_birth',
            'admission_date', 'status', 'teacher_id'
        ]));

        return response()->json(['message' => '✅ Student updated successfully', 'student' => $student]);
    }

    
    public function destroy(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $user = User::find($student->user_id);
        $loggedUser = Auth::user();

        if (($loggedUser->role === 'teacher' || $request->is('api/teacher-students*')) &&
            $student->teacher_id !== Teacher::where('user_id', $loggedUser->id)->value('id')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($user) $user->delete();
        $student->delete();

        return response()->json(['message' => '✅ Student deleted successfully']);
    }
}
