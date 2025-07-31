<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    /**
     * ✅ List all teachers (Admin only)
     */
    public function index()
    {
        return response()->json(Teacher::with('user')->get());
    }

    /**
     * ✅ Create teacher (Admin only)
     */
    public function store(Request $request)
    {
        $request->validate([
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

        // ✅ Create login user
        $user = User::create([
            'name'     => $request->first_name . ' ' . $request->last_name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'teacher',
        ]);

        // ✅ Create teacher profile
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

    /**
     * ✅ Show single teacher
     */
    public function show($id)
    {
        $teacher = Teacher::with('user')->findOrFail($id);
        return response()->json($teacher);
    }

    /**
     * ✅ Update teacher (Admin only)
     */
    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);
        $user = User::findOrFail($teacher->user_id);

        $request->validate([
            'first_name'            => 'sometimes|required|string',
            'last_name'             => 'sometimes|required|string',
            'email'                 => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone_number'          => 'sometimes|required|string',
            'subject_specialization'=> 'sometimes|required|string',
            'employee_id'           => 'sometimes|required|string|unique:teachers,employee_id,' . $teacher->id,
            'date_of_joining'       => 'sometimes|required|date',
            'status'                => 'sometimes|required|in:Active,Inactive',
        ]);

        // ✅ Update user info
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('first_name') || $request->has('last_name')) {
            $user->name = ($request->first_name ?? $teacher->first_name) . ' ' . ($request->last_name ?? $teacher->last_name);
        }
        $user->save();

        // ✅ Update teacher profile
        $teacher->update($request->only([
            'first_name', 'last_name', 'email', 'phone_number',
            'subject_specialization', 'employee_id', 'date_of_joining', 'status'
        ]));

        return response()->json(['message' => 'Teacher updated successfully', 'teacher' => $teacher]);
    }

    /**
     * ✅ Delete teacher (Admin only)
     */
    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);
        $user = User::find($teacher->user_id);

        if ($user) {
            $user->delete(); // delete linked user
        }

        $teacher->delete();

        return response()->json(['message' => 'Teacher deleted successfully']);
    }
}
