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
    /**
     * ✅ Register user
     * - First admin can register without being logged in.
     * - After first admin exists:
     *      → Only logged-in admin can register teachers/students.
     *      → Logged-in teacher can register students only.
     */
    public function register(Request $request)
    {
        // ✅ Check if any admin already exists
        $adminExists = User::where('role', 'admin')->exists();

        // ✅ Basic validation for user table
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role'     => 'required|in:admin,teacher,student',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        /**
         * 🚨 If no admin exists yet:
         * ✅ Allow creating first admin without login check
         */
        if (!$adminExists && $request->role === 'admin') {
            return $this->createUserAndProfile($request);
        }

        /**
         * ✅ After first admin exists, require authentication
         */
        $authUser = auth()->user();

        if (!$authUser) {
            return response()->json([
                'error' => 'Only logged-in admin or teacher can register users'
            ], 403);
        }

        // 🚫 Teachers cannot create admins or teachers
        if ($authUser->role === 'teacher' && $request->role !== 'student') {
            return response()->json(['error' => 'Teachers can only register students'], 403);
        }

        // 🚫 Only admins can create another admin
        if ($authUser->role !== 'admin' && $request->role === 'admin') {
            return response()->json(['error' => 'Only admin can register another admin'], 403);
        }

        // ✅ Create teacher/student
        return $this->createUserAndProfile($request);
    }

    /**
     * ✅ Helper: Create User + Teacher/Student profile
     */
    private function createUserAndProfile($request)
    {
        // ✅ Create user account
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        $profile = null;

        // ✅ If Teacher, create Teacher profile
        if ($request->role === 'teacher') {
            $request->validate([
                'first_name'            => 'required|string',
                'last_name'             => 'required|string',
                'phone_number'          => 'required|string',
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

        // ✅ If Student, create Student profile
        if ($request->role === 'student') {
            $request->validate([
                'first_name'      => 'required|string',
                'last_name'       => 'required|string',
                'phone_number'    => 'required|string',
                'roll_number'     => 'required|string|unique:students',
                'class'           => 'required|string',
                'date_of_birth'   => 'required|date',
                'admission_date'  => 'required|date',
                'status'          => 'required|in:Active,Inactive',
                'assigned_teacher'=> 'nullable|exists:teachers,id'
            ]);

            // ✅ If logged-in user is a teacher, auto-assign their ID
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

    /**
     * ✅ Login user
     */
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

    /**
     * ✅ Get logged-in user info
     */
    public function me()
    {
        return response()->json(auth()->user());
    }
   


    
    /**
     * ✅ Logout user
     */
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }
}
