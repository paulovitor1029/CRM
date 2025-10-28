<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ItemController
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Item::query()->orderBy('created_at', 'desc')->get(['id', 'name'])]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $item = Item::create($validated);
        return response()->json(['data' => $item], Response::HTTP_CREATED);
    }

    public function update(Request $request, Item $item): JsonResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $item->update($validated);
        return response()->json(['data' => $item]);
    }

    public function destroy(Item $item): JsonResponse
    {
        $item->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}

