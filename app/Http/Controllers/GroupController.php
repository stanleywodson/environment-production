<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GroupController extends Controller
{
	public function index()
	{
		$domains = Group::with(['domains', 'emails'])->get();
		return response()->json($domains);
	}

	public function getGroupByUser($userId)
	{
		$groups = Group::where('user_id', $userId)->with(['domains', 'emails'])->get();

		if ($groups->isEmpty()) {
			return response()->json(['message' => 'Nenhum grupo encontrado para este usuário.'], 404);
		}

		return response()->json($groups, 200);
	}

	public function store(Request $request)
	{
		$request->validate([
			'domains' => 'required|array',
			'domains.*' => 'required|string',
			'emails' => 'required|array',
			'emails.*' => 'required|email',
			'user_id' => 'required|integer',
		]);

		$group = Group::create([
			'name' => 'Group_' . request('user_id') . '_' . Str::random(8),
			'user_id' => request('user_id'),
		]);

		$group->domains()->createMany(array_map(function ($domain) use ($group) {
			return [
				'group_id' => $group->id,
				'domain' => $domain,
			];
		}, request('domains')));

		$group->emails()->createMany(array_map(function ($email) use ($group) {
			return [
				'group_id' => $group->id,
				'email' => $email,
			];
		}, request('emails')));

		return response()->json($group->load(['domains', 'emails']), 201);
	}
	
	public function show(Group $group)
	{
		return response()->json($group->load(['domains', 'emails']));
	}
	
	public function update(Request $request, Group $group)
	{
		$request->validate([
			'domains' => 'required|array',
			'domains.*' => 'required|string',
			'emails' => 'required|array',
			'emails.*' => 'required|email',
		]);

		
		$group->domains()->delete();
		$group->emails()->delete();

		$group->domains()->createMany(array_map(function ($domain) use ($group) {
			return [
				'group_id' => $group->id,
				'domain' => $domain,
			];
		}, request('domains')));

		$group->emails()->createMany(array_map(function ($email) use ($group) {
			return [
				'group_id' => $group->id,
				'email' => $email,
			];
		}, request('emails')));

		return response()->json($group->load(['domains', 'emails']));
	}
	
	public function destroy(Group $group)
	{
		$group->domains()->delete();
		$group->emails()->delete();
		$group->delete();

		return response()->json(['message' => 'Group deleted successfully']);
	}
}
