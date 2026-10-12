<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', 'confirmed', 'min:8'], 'device_name' => ['required', 'string', 'max:255']]);
        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);

        return response()->json(['user' => $user, 'token' => $user->createToken($data['device_name'])->plainTextToken], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required'], 'device_name' => ['required', 'string', 'max:255']]);
        $user = User::where('email', $data['email'])->first();
        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

return response()->json(['user' => $user, 'token' => $user->createToken($data['device_name'])->plainTextToken]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([], 204);
    }
}
