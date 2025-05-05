<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
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
                $tokenInfo = $user->first()->createToken('authToken');
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
    public function me(Request $request)
    {
        return response()->json([
            'message' => 'Logged User Information',
            'user' => $request->user()
        ], 200);
    }
    public function logout()
    {
        request()->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged Out',
        ], 200);
    }
}
