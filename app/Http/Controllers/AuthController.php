<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
   // Register a new user
   public function register(Request $request)
   {
      $request->validate([
         'name' => 'required|string|max:255',
         'email' => 'required|string|email|max:255|unique:users',
         'role' => 'required|string',
         'password' => 'required|string|min:8',
      ]);

      $user = User::create([
         'name' => $request->name,
         'email' => $request->email,
         'role' => $request->role,
         'password' => Hash::make($request->password),
      ]);

      return response()->json([
         'message' => 'User registered successfully!',
         'user' => $user,
      ], 201);
   }

   // Login user and create token
   public function login(Request $request)
   {
      $request->validate([
         'email' => 'required|string|email',
         'password' => 'required|string',
      ]);

      $user = User::where('email', $request->email)->first();

      if (! $user || ! Hash::check($request->password, $user->password)) {
         return response()->json([
            'message' => 'Unauthorized'
         ], 401);
      }

      $token = $user->createToken('auth_token')->plainTextToken;

      return response()->json([
         'id' => $user->id,
         'name' => $user->name,
         'email' => $user->email,
         'role' => $user->role,
         'access_token' => $token,
      ]);
   }

   // Logout user (Revoke the token)
   public function logout(Request $request)
   {
      $request->user()->currentAccessToken()->delete();

      return response()->json([
         'message' => 'Logout successful!',
      ]);
   }

   // Get the authenticated user
   public function me(Request $request)
   {
      return response()->json($request->user());
   }
}
