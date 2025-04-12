<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\InvalidFilesController;
use App\Http\Controllers\HashController;
use App\Http\Controllers\LargeFileController;
use App\Http\Controllers\UserDomainController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('invalidfiles', [InvalidFilesController::class, 'index']);

# Login Cerbero
Route::post('auth-cerbero', [\App\Http\Controllers\Auth\AuthCerberoController::class, 'cerbero']);

Route::middleware('auth:sanctum')->group(function () {
	
	// Search Files
	Route::post('/files/search', [LargeFileController::class, 'search']);
	Route::post('/files/search-by-mailisearch', [LargeFileController::class, 'searchByMailisearch']);
	Route::post('/upload', [FileUploadController::class, 'upload']);

	// cerbero logout
	Route::post('logout-cerbero', [\App\Http\Controllers\Auth\AuthCerberoController::class, 'logout']);
	
	// Groups Domains
	Route::apiResource('groups', GroupController::class);
	Route::get('groups-user/{userId}', [GroupController::class, 'getGroupByUser']);
	Route::post('/files/searchv2', [LargeFileController::class, 'searchv2']);
	Route::apiResource('/hash', HashController::class);

	Route::post('/download', [InvalidFilesController::class, 'download']);
	Route::post('/file-destroy', [InvalidFilesController::class, 'destroy']);


	Route::get('/executar-comando', function () {
		Artisan::call('add-index');

		return response()->json(['message' => 'Comando executado com sucesso!']);
	});

	Route::get('/executar-records', function () {
		Artisan::call('app:consolidate-records');

		return response()->json(['message' => 'Comando executado com sucesso!']);
	});
});

require __DIR__ . '/auth.php';