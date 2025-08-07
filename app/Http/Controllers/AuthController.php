<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AuthController extends Controller
{
    public function register(Request $request)
{
    $adminExists = User::where('role', 'admin')->exists();

    if ($request->role !== 'admin') {
        return response()->json(['error' => 'Only admin registration is allowed via this endpoint'], 403);
    }

    if ($adminExists) {
        return response()->json(['error' => 'Admin already exists. Registration is disabled.'], 403);
    }

    $validator = Validator::make($request->all(), [
        'name'     => 'required|string|max:100',
        'email'    => 'required|email|unique:users',
        'password' => 'required|min:6',
        'role'     => 'required|in:admin',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    return $this->createUserAndProfile($request);
}


   private function createUserAndProfile($request)
{
    $user = User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => Hash::make($request->password),
        'role'     => 'admin',
    ]);

    return response()->json([
        'message' => 'Admin registered successfully',
        'user'    => $user,
    ], 201);
}


    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$accessToken = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $refreshToken = $this->createRefreshToken();
        $user = auth()->user();

        return response()->json([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'bearer',
            'expires_in'    => config('jwt.ttl') * 60,
            'user'          => $user
        ]);
    }

    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string'
        ]);

        try {
            $payload = JWTAuth::setToken($request->refresh_token)->getPayload();

            if ($payload->get('type') !== 'refresh') {
                return response()->json(['error' => 'Invalid refresh token'], 401);
            }

            $user = User::find($payload->get('sub'));

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $newAccessToken = JWTAuth::fromUser($user);
            $newRefreshToken = $this->createRefreshToken($user);

            return response()->json([
                'access_token'  => $newAccessToken,
                'refresh_token' => $newRefreshToken,
                'token_type'    => 'bearer',
                'expires_in'    => config('jwt.ttl') * 60
            ]);

        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Refresh token expired'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Invalid refresh token'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token error'], 500);
        }
    }

    private function createRefreshToken($user = null)
    {
        $user = $user ?: auth()->user();

        $payload = [
            'sub' => $user->id,
            'type' => 'refresh',
            'iat' => now()->timestamp,
            'exp' => now()->addDays(30)->timestamp,
        ];

        return JWTAuth::getJWTProvider()->encode($payload);
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
