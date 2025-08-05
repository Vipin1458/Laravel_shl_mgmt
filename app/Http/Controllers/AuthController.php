<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
  
    public function register(Request $request)
    {
        $adminExists = User::where('role', 'admin')->exists();

        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role'     => 'required|in:admin,teacher,student',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->role === 'admin') {
            $request->validate([
                'name' => 'required|string'
            ]);
        }

        if (!$adminExists && $request->role === 'admin') {
            return $this->createUserAndProfile($request);
        }

        $authUser = auth()->user();
        if (!$authUser) {
            return response()->json(['error' => 'Only logged-in admin or teacher can register users'], 403);
        }

        if ($authUser->role === 'teacher' && $request->role !== 'student') {
            return response()->json(['error' => 'Teachers can only register students'], 403);
        }

        if ($authUser->role !== 'admin' && $request->role === 'admin') {
            return response()->json(['error' => 'Only admin can register another admin'], 403);
        }

        return $this->createUserAndProfile($request);
    }

   
    private function createUserAndProfile($request)
    {
        $userName = $request->role === 'admin'
            ? $request->name
            : trim($request->first_name . ' ' . $request->last_name);

        $user = User::create([
            'name'     => $userName,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        $profile = null;

        if ($request->role === 'teacher') {
            $request->validate([
                'first_name'            => 'required|string|min:2|max:50',
                'last_name'             => 'required|string|max:50',
                'phone_number'          => 'required|digits:10',
                'subject_specialization'=> 'required|string',
                'employee_id'           => 'required|string|unique:teachers',
                'date_of_joining'       => 'required|date',
                'status'                => 'required|in:Active,Inactive'
            ]);

            $profile = Teacher::create([
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
        }

        if ($request->role === 'student') {
            $request->validate([
                'first_name'      => 'required|string|min:2|max:50',
                'last_name'       => 'nullable|string|max:50',
                'phone_number'    => 'required|digits:10',
                'roll_number'     => 'required|string|unique:students',
                'class'           => 'required|string',
                'date_of_birth'   => 'required|date',
                'admission_date'  => 'required|date',
                'status'          => 'required|in:Active,Inactive',
                'assigned_teacher'=> 'nullable|exists:teachers,id'
            ]);

            $teacherId = $request->assigned_teacher;
            if (auth()->check() && auth()->user()->role === 'teacher') {
                $teacherId = Teacher::where('user_id', auth()->id())->value('id');
            }

            $profile = Student::create([
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
        }

        return response()->json([
            'message' => ucfirst($request->role) . ' registered successfully',
            'user'    => $user,
            'profile' => $profile
        ], 201);
    }

  
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'access_token' => $token,
            'user' => auth()->user()
        ]);
    }

    
    public function me()
    {
        return response()->json(auth()->user());
    }

    
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }
}
