<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @OA\SecurityScheme(
     *     securityScheme="bearerAuth",
     *     type="http",
     *     scheme="bearer",
     *     bearerFormat="JWT",
     * )
     * 
     * @OA\Response(
     *     response=403,
     *     description="Forbidden",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Unauthorized access"),
     *         @OA\Property(property="required_role", type="string", example="admin"),
     *         @OA\Property(property="error_code", type="integer", example=1001)
     *     )
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $role
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ?string $role = 'admin'): Response
    {
        $user = $request->user();

        if (!$user) {
            Log::warning('Unauthenticated access attempt', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'method' => $request->method()
            ]);

            return response()->json([
                'message' => 'Authentication required',
                'error_code' => 1000
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->checkUserRole($user, $role)) {
            Log::warning('Unauthorized role access attempt', [
                'user_id' => $user->id,
                'attempted_role' => $role,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'message' => 'Insufficient privileges',
                'required_role' => $role,
                'error_code' => 1001,
                'current_roles' => $user->roles()->pluck('name') 
            ], Response::HTTP_FORBIDDEN);
        }

        $request->attributes->add(['auth_user_role' => $role]);

        return $next($request);
    }

    /**
     *
     * @param \App\Models\User $user
     * @param string $role
     * @return bool
     */
    protected function checkUserRole($user, string $role): bool
    {
        if (method_exists($user, "is{$role}")) {
            return call_user_func([$user, "is{$role}"]);
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($role);
        }

        return $user->role === $role;
    }
}
