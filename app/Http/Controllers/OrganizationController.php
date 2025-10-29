<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class OrganizationController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user && method_exists($user,'isSuperAdmin') && $user->isSuperAdmin()) {
            $orgs = Organization::orderBy('name')->get();
        } else if ($user) {
            $orgs = $user->organizations()->orderBy('name')->get();
        } else {
            $orgs = collect();
        }
        $active = (string) ($request->session()->get('organization_id') ?? '');
        return response()->json(['data' => $orgs, 'active' => $active]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'slug' => ['nullable','string','max:64'],
            'settings' => ['array'],
        ]);
        $slug = $data['slug'] ?? Str::slug($data['name']).'-'.Str::random(4);
        $org = Organization::create([
            'name' => $data['name'],
            'slug' => $slug,
            'created_by' => $user->id,
            'settings' => $data['settings'] ?? [],
            'status' => 'active',
        ]);
        return response()->json(['data' => $org], Response::HTTP_CREATED);
    }

    public function update(string $id, Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $org = Organization::findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes','string','max:255'],
            'slug' => ['sometimes','string','max:64'],
            'status' => ['sometimes','in:active,inactive'],
            'settings' => ['sometimes','array'],
        ]);
        $org->fill($data)->save();
        return response()->json(['data' => $org]);
    }

    public function destroy(string $id, Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) { return response()->json(['message' => 'Forbidden'], 403); }
        $org = Organization::findOrFail($id);
        $org->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function members(string $id): JsonResponse
    {
        $org = Organization::findOrFail($id);
        return response()->json(['data' => $org->users]);
    }

    public function addMember(string $id, Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) { return response()->json(['message' => 'Forbidden'], 403); }
        $org = Organization::findOrFail($id);
        $data = $request->validate([
            'user_id' => ['required','uuid','exists:users,id'],
            'role' => ['required','in:org_admin,member'],
        ]);
        $org->users()->syncWithoutDetaching([$data['user_id'] => ['role' => $data['role'], 'invited_at' => now(), 'accepted_at' => now()]]);
        return response()->json(['data' => $org->users]);
    }

    public function setMemberRole(string $id, string $userId, Request $request): JsonResponse
    {
        $actor = $request->user();
        if (!$actor || !$actor->isSuperAdmin()) { return response()->json(['message' => 'Forbidden'], 403); }
        $org = Organization::findOrFail($id);
        $data = $request->validate(['role' => ['required','in:org_admin,member']]);
        $org->users()->updateExistingPivot($userId, ['role' => $data['role']]);
        return response()->json(['data' => $org->users]);
    }

    public function switcher(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgs = $user ? $user->organizations : collect();
        $active = (string) ($request->session()->get('organization_id') ?? '');
        return response()->json(['organizations' => $orgs, 'active' => $active]);
    }

    public function switch(string $id, Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);
        if (!$user->isSuperAdmin() && !$user->organizations()->where('organizations.id',$id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $request->session()->put('organization_id', $id);
        return response()->json(['organization_id' => $id]);
    }
}

