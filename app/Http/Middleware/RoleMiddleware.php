<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;

class RoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate(); // ✅ Get logged-in user via JWT

            if (!in_array($user->role, $roles)) {
                return response()->json(['error' => 'Forbidden – role mismatch'], 403);
            }

            return $next($request);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Unauthorized – token missing or invalid'], 401);
        }
    }
}
