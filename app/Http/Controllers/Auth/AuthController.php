<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/user/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", example="Kaan Uçarcı"),
     *             @OA\Property(property="email", type="string", format="email", example="kaan@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User created successfully",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="User created successfully"))
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     )
     * )
     */

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            $response = [
                'message' => $validator->errors()->first(),
            ];
            return response()->json($response, 400);
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => password_hash($request->password, PASSWORD_BCRYPT),
        ]);

        return response()->json(['message' => 'User created successfully',], 200);
    }
    /**
     * @OA\Post(
     *     path="/user/login",
     *     tags={"Authentication"},
     *     summary="Login a user and get access token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="kaan@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logged in successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logged in successfully"),
     *             @OA\Property(property="token", type="string", example="Bearer token..."),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Wrong password or validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails())
            return response()->json(['message' => $validator->errors()->first()], 400);


        $user = User::where('email', $request->email);

        if ($user->exists())
        {
            if (password_verify($request->password, $user->first()->password))
            {
                $credentials = $request->only('email', 'password');
                $tokenInfo = $user->first()->createToken(implode(',', $credentials));
                $tokenInfo->accessToken->expires_at = now()->addHours(2);
                $tokenInfo->accessToken->save();

                $token = $tokenInfo->plainTextToken;

                return response()->json([
                    'message' => 'Logged in successfully',
                    'token' => $token,
                    'user' => $user->first(),
                ], 200);
            }
            else
                return response()->json(['message' => 'Wrong password'], 400);
        }
        else
            return response()->json(['message' => 'User not found'], 404);

    }

    /**
     * @OA\Get(
     *     path="/user/me",
     *     tags={"Authentication"},
     *     summary="Get the logged-in user's info",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logged user information",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logged User Information"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */

    public function me(Request $request)
    {
        return response()->json([
            'message' => 'Logged User Information',
            'user' => $request->user()
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/user/logout",
     *     tags={"Authentication"},
     *     summary="Logout and revoke user tokens",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User logged out",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logged Out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */

    public function logout()
    {
        request()->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged Out',
        ], 200);
    }
}
