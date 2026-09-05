<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $defaultCategories = [
            ['name' => 'Electricity', 'default_type' => 'fixed', 'is_default' => true],
            ['name' => 'Gas', 'default_type' => 'fixed', 'is_default' => true],
            ['name' => 'Water', 'default_type' => 'fixed', 'is_default' => true],
        ];

        foreach ($defaultCategories as $category) {
            Category::forceCreate([
                'name' => $category['name'],
                'default_type' => $category['default_type'],
                'is_default' => $category['is_default'],
                'user_id' => $user->id,
            ]);
        }

        $token = $user->createToken('auth-token');

        return UserResource::make($user)->withToken($token->plainTextToken)->response()->setStatusCode(201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth-token');

        return UserResource::make($user)->withToken($token->plainTextToken)->response();
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    public function user(Request $request): JsonResponse
    {
        return (new UserResource($request->user()))->response();
    }
}
