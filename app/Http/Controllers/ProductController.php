<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController
{
    public function index(Request $request): JsonResponse
    {
        $org = (string) ($request->attributes->get('organization_id') ?? $request->query('organization_id') ?? 'default');
        $list = Product::with('category')->where('organization_id', $org)->orderBy('name')->get();
        return response()->json(['data' => $list]);
    }

    public function store(ProductStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['organization_id'] = $data['organization_id'] ?? (string) ($request->attributes->get('organization_id') ?? $request->query('organization_id') ?? 'default');
        $product = Product::create($data);
        // metadata no longer stored in separate table
        return response()->json(['data' => $product->load('category')], Response::HTTP_CREATED);
    }
}
