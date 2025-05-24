<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    private const TOKEN_NAME = 'auth_token';
    private const TOKEN_EXPIRATION_HOURS = 2;
    
    /**
     * @OA\Post(
     *     path="/user/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     operationId="registerUser",
     *     @OA\RequestBody(
     *         required=true,
     *         description="User registration data",
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Kaan Uçarcı"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, example="kaan@example.com"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8, example="Secret123!"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Secret123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User created successfully"),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verification_token' => Str::random(60),
        ]);

        // در اینجا می‌توانید ایمیل تایید را ارسال کنید
        // $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->makeHidden(['password', 'email_verification_token'])
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/user/login",
     *     tags={"Authentication"},
     *     summary="Authenticate user and get access token",
     *     operationId="loginUser",
     *     @OA\RequestBody(
     *         required=true,
     *         description="User credentials",
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="kaan@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Secret123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authentication successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logged in successfully"),
     *             @OA\Property(property="token", type="string", example="1|AbCdEfGhIjKlMnOpQrStUvWxYz"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=7200),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Email not verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Please verify your email address")
     *         )
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // اگر ایمیل تایید شده باشد
        // if (!$user->hasVerifiedEmail()) {
        //     return response()->json([
        //         'message' => 'Please verify your email address'
        //     ], 403);
        // }

        $token = $user->createToken(
            self::TOKEN_NAME,
            ['*'],
            now()->addHours(self::TOKEN_EXPIRATION_HOURS)
        )->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully',
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => self::TOKEN_EXPIRATION_HOURS * 3600,
            'user' => $user->makeHidden(['password', 'email_verification_token'])
        ]);
    }

    /**
     * @OA\Get(
     *     path="/user/me",
     *     tags={"Authentication"},
     *     summary="Get authenticated user details",
     *     operationId="getAuthenticatedUser",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->makeHidden(['password', 'email_verification_token'])
        ]);
    }

    /**
     * @OA\Post(
     *     path="/user/logout",
     *     tags={"Authentication"},
     *     summary="Logout user and revoke tokens",
     *     operationId="logoutUser",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=204,
     *         description="Successfully logged out",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function logout(): JsonResponse
    {
        auth()->user()->tokens()->delete();

        return response()->json(null, 204);
    }

    /**
     * @OA\Post(
     *     path="/user/refresh-token",
     *     tags={"Authentication"},
     *     summary="Refresh authentication token",
     *     operationId="refreshToken",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="2|AbCdEfGhIjKlMnOpQrStUvWxYz"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=7200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function refreshToken(): JsonResponse
    {
        $user = auth()->user();
        $user->tokens()->delete();

        $token = $user->createToken(
            self::TOKEN_NAME,
            ['*'],
            now()->addHours(self::TOKEN_EXPIRATION_HOURS)
        )->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => self::TOKEN_EXPIRATION_HOURS * 3600
        ]);
    }
}
