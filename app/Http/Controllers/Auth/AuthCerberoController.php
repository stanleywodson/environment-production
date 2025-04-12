<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Nette\Utils\Random;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\json;

class AuthCerberoController extends Controller
{
	//	public function index()
	//	{
	//		$pageConfigs = ['myLayout' => 'blank'];
	//		return view('content.authentications.auth-login-basic', ['pageConfigs' => $pageConfigs]);
	//	}

	public function store(Request $request)
	{
		$credentials = $request->only('email', 'password');

		if (env('CERBERO_AUTHENTICATION')) {
			$jwt = Http::withoutVerifying()
				->withHeaders([
					'Accept' => 'application/json',
					'Origin' => 'horus.dataverso.net'
				])->POST(env('CERBERO_LOGIN_URL'), [
					'email' => $credentials['email'],
					'password' => $credentials['password'],
					'secret' => env('CERBERO_SECRET')
				]);

			if ($jwt->failed()) {
				return response()->json('error', 'Credenciales incorrectas');
			}

			$jwk = cache()->remember('cerbero_jwk', 3600, function () {
				return Http::withoutVerifying()->get(env('CERBERO_JWK_URL'))->json();
			});

			try {
				$decode = JWT::decode($jwt->json()['token'], JWK::parseKeySet($jwk));
			} catch (\Throwable $th) {
				return response()->json('error', 'Error ao verificar credenciais');
			}

			$user = User::where('uuid', $decode->profile->uuid)->first();

			if (is_null($user)) {
				$user = User::create([
					'uuid' => $decode->profile->uuid,
					'name' => $decode->profile->name,
					'email' => $decode->profile->email,
					'password' => bcrypt($credentials['password']),
					'uuid' => $decode->profile->uuid,

				]);
			}

			// Sincroniza os roles do usuário
			if (isset($decode->roles) && is_array($decode->roles)) {
				// Remove todos os roles atuais
				$user->syncRoles([]);

				foreach ($decode->roles as $roleName) {
					// Verifica se o role existe, se não, cria
					$role = Role::firstOrCreate(['name' => $roleName]);

					// Atribui o role ao usuário
					if (!$user->hasRole($role)) {
						$user->assignRole($role);
					}
				}
			}

			if ($user) {
				return response()->json([
					'id' => $user->id,
					'name' => $user->name,
					'email' => $user->email,
					'role' => $user->role,
					'access_token' => $token,
				]);
			}

			return response()->json('error', 'Credenciales incorrectas');
		} else {
			if (FacadesAuth::attempt($credentials)) {
				$request->session()->regenerate();
				return redirect()->intended('dashboard');
			}

			return back()->withErrors([
				'email' => 'The provided credentials do not match our records.',
			]);
		}
	}

	public function cerbero(Request $request)
	{
		$request->validate(['token' => 'required']);

		try {
			// Obtenha o JWK e verifique a resposta
			$response = Http::withoutVerifying()->get(config('cerbero.jwk_url'));
			if (!$response->successful()) {
				return response()->json(['error' => 'Falha ao obter chave pública.'], 500);
			}
			$jwk = $response->json();

			// Decodificar o token
			$decode = JWT::decode($request->token, JWK::parseKeySet($jwk));

			// Validar se os dados necessários estão presentes
			if (!isset($decode->profile->uuid) || !isset($decode->profile->name) || !isset($decode->profile->email)) {
				return response()->json(['error' => 'Dados do token inválidos.'], 400);
			}

			// Criar ou obter o usuário
			$user = User::firstOrCreate(
				['email' => $decode->profile->email],
				[
					'name' => $decode->profile->name,
					'uuid' => $decode->profile->uuid,
					'password' => bcrypt(Random::generate(8)),
					'role' => 'client',
				]
			);

			// Gerar o token de acesso
			$user_token = $user->createToken('auth_token')->plainTextToken;
			$session_timer = $decode->session_timer;

			// Retornar resposta com os dados do usuário
			return response()->json([
				'id' => $user->id,
				'name' => $user->name,
				'email' => $user->email,
				'role' => $user->role,
				'access_token' => $user_token,
				'expires' => $session_timer,
			]);
		} catch (\Throwable $th) {
			// Log do erro
			Log::error('Erro na autenticação', [
				'error' => $th->getMessage(),
				'trace' => $th->getTraceAsString(),
			]);

			return response()->json(['error' => 'Token inválido ou erro na comunicação com a API.'], 400);
		}
	}

	public function logout(Request $request)
	{
		$request->user()->currentAccessToken()->delete();
		return response()->json(['message' => 'Logged out successfully.']);
	}
}