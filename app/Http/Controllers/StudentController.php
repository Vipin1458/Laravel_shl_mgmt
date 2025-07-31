<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    /**
     * Display all students (Admin sees all, Teacher sees only own)
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'teacher') {
            // ✅ Teacher sees only their assigned students
            return response()->json(Student::where('teacher_id', $user->teacher->id)->get());
        }

        // ✅ Admin sees all students
        return response()->json(Student::with('teacher')->get());
    }

    /**
     * Show a single student
     */
    public function show($id)
    {
        $student = Student::with('teacher')->find($id);

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        // ✅ Teacher can only view own students
        if (Auth::user()->role === 'teacher' && $student->teacher_id !== Auth::user()->teacher->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($student);
    }

    /**
     * Update student (Admin can update all, Teacher can update own only)
     */
    public function update(Request $request, $id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        if (Auth::user()->role === 'teacher' && $student->teacher_id !== Auth::user()->teacher->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $student->update($request->only([
            'first_name',
            'last_name',
            'phone_number',
            'roll_number',
            'class',
            'date_of_birth',
            'admission_date',
            'status',
            'teacher_id' // ✅ Admin can reassign teacher
        ]));

        return response()->json(['message' => 'Student updated successfully', 'student' => $student]);
    }

    /**
     * Delete student (Admin can delete any, Teacher can delete only own)
     */
    public function destroy($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        if (Auth::user()->role === 'teacher' && $student->teacher_id !== Auth::user()->teacher->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $student->delete();

        return response()->json(['message' => 'Student deleted successfully']);
    }
}
