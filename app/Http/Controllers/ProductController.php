<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Models\Product;
use App\Models\ProductMetadata;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController
{
    public function index(Request $request): JsonResponse
    {
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $list = Product::with('category')->where('tenant_id', $tenant)->orderBy('name')->get();
        return response()->json(['data' => $list]);
    }

    public function store(ProductStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $data['tenant_id'] ?? 'default';
        $product = Product::create($data);
        if (!empty($data['metadata'])) {
            ProductMetadata::create(['product_id' => $product->id, 'data' => $data['metadata']]);
        }
        return response()->json(['data' => $product->load('category')], Response::HTTP_CREATED);
    }
}

