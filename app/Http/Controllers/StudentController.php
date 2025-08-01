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
            \Log::info("Teacher {$user->email} is fetching their own students (Teacher ID: {$teacherId})");

            return response()->json(
                Student::where('teacher_id', $teacherId)->with('user')->get()
            );
        }

        \Log::info("Admin {$user->email} is fetching ALL students");
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
            \Log::warning("Validation failed when creating student", $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loggedUser = Auth::user();

        if ($loggedUser->role === 'teacher' || $request->is('api/teacher-students*')) {
            $teacherId = Teacher::where('user_id', $loggedUser->id)->value('id');
            \Log::info("Teacher {$loggedUser->email} is creating a student. Auto-assigning teacher_id: {$teacherId}");
        } else {
            $teacherId = $request->teacher_id;
            \Log::info("Admin {$loggedUser->email} is creating a student. Teacher assigned: " . ($teacherId ?? 'none'));
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

        return response()->json(['message' => 'Student created successfully', 'student' => $student], 201);
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
            
            \Log::warning("Teacher {$loggedUser->email} tried to access student {$student->id} not assigned to them");
            return response()->json(['error' => ' You are not authorized to view this student'], 403);
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

            \Log::warning("Teacher {$loggedUser->email} tried to UPDATE student {$student->id} not assigned to them");
            return response()->json(['error' => ' You are not authorized to update this student'], 403);
        }

        if ($loggedUser->role === 'teacher' && $request->has('teacher_id')) {
            \Log::warning("Teacher {$loggedUser->email} tried to override teacher_id on student update");
            unset($request['teacher_id']); 
        }

        $validator = Validator::make($request->all(), [
            'email'       => 'sometimes|required|email|unique:users,email,' . $user->id,
            'roll_number' => 'sometimes|required|string|unique:students,roll_number,' . $student->id,
        ]);

        if ($validator->fails()) {
            \Log::warning("Validation failed on update", $validator->errors()->toArray());
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

        return response()->json(['message' => 'Student updated successfully', 'student' => $student]);
    }

    public function destroy(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $user = User::find($student->user_id);
        $loggedUser = Auth::user();

        if (($loggedUser->role === 'teacher' || $request->is('api/teacher-students*')) &&
            $student->teacher_id !== Teacher::where('user_id', $loggedUser->id)->value('id')) {

            \Log::warning("Teacher {$loggedUser->email} tried to DELETE student {$student->id} not assigned to them");
            return response()->json(['error' => ' You are not authorized to delete this student'], 403);
        }

        if ($user) $user->delete();
        $student->delete();

        return response()->json(['message' => ' Student deleted successfully']);
    }

public function studentsByTeacher($teacherId)
{
    $authUser = auth()->user();

    if ($authUser->role !== 'admin') {
        return response()->json(['error' => 'Only admins can view students by teacher'], 403);
    }

    $teacher = Teacher::find($teacherId);
    if (!$teacher) {
        return response()->json(['error' => 'Teacher not found'], 404);
    }

    $students = Student::where('teacher_id', $teacherId)->with('user')->get();

    return response()->json([
        'teacher' => $teacher,
        'students' => $students
    ]);
}


public function myProfile()
{
    $authUser = auth()->user();

    if ($authUser->role !== 'student') {
        return response()->json(['error' => 'Only students can access this'], 403);
    }

    $student = Student::with('user')->where('user_id', $authUser->id)->firstOrFail();

    return response()->json($student);
}


public function updateProfile(Request $request)
{
    $authUser = auth()->user();

    if ($authUser->role !== 'student') {
        return response()->json(['error' => 'Only students can update their profile'], 403);
    }

    $student = Student::where('user_id', $authUser->id)->firstOrFail();
    $user = $authUser;

    $request->validate([
        'first_name'   => 'sometimes|required|string',
        'last_name'    => 'sometimes|required|string',
        'email'        => 'sometimes|required|email|unique:users,email,' . $user->id,
        'phone_number' => 'sometimes|required|string',
        'status'       => 'sometimes|required|in:Active,Inactive',
    ]);

    if ($request->has('email')) $user->email = $request->email;
    if ($request->has('first_name') || $request->has('last_name')) {
        $user->name = ($request->first_name ?? $student->first_name) . ' ' .
                      ($request->last_name ?? $student->last_name);
    }
    $user->save();

    $student->update($request->only(['first_name', 'last_name', 'phone_number', 'email', 'status']));

    return response()->json(['message' => 'Student profile updated successfully', 'student' => $student]);
}


}
