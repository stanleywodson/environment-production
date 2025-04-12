<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthenticatedSessionController extends Controller
{
	/**
	 * Handle an incoming authentication request.
	 */
	public function store(LoginRequest $request): JsonResponse
	{
		$request->authenticate();

		$token = $request->user()->createToken(
			'auth_token',
			// ['*'],
			// now()->addDays(1)
		)->plainTextToken;

		return response()->json([
			'id' => $request->user()->id,
			'name' => $request->user()->name,
			'email' => $request->user()->email,
			'role' => $request->user()->role,
			'access_token' => $token,
			// 'expires' => now()->addDays(1)->toDateTimeString()
		]);
	}

	/**
	 * Destroy an authenticated session.
	 */
	public function destroy(Request $request): JsonResponse
	{
		$request->user()->currentAccessToken()->delete();
		return response()->json(['message' => 'User logged out successfully!'], 200);
	}
}
