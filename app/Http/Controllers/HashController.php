<?php

namespace App\Http\Controllers;

use App\Models\Hash;
use Illuminate\Http\Request;

class HashController extends Controller
{
	public function index()
	{
		//
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{

	}

	/**
	 * Display the specified resource.
	 */
	public function show(string $hash)
	{
		$result = cache()->remember("search_hashe_{$hash}", 30, function () use ($hash) {
			return Hash::where('md5hash', $hash)
				->orWhere('sha1hash', $hash)
				->orWhere('sha256hash', $hash)
				->orWhere('senha', $hash)
				->first();
		});
		
		return response()->json($result, 200);
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, Hash $hash)
	{
		//
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(Hash $hash)
	{
		//
	}
}
