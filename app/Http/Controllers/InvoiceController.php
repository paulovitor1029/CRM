<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController
{
    public function __construct(private readonly BillingService $billing)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $list = Invoice::where('tenant_id', $tenant)->orderByDesc('issued_at')->paginate(20);
        return response()->json(['data' => $list->items(), 'meta' => ['current_page' => $list->currentPage()]]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subscription_id' => ['required','uuid','exists:subscriptions,id'],
            'bill_at' => ['nullable','date'],
        ]);
        $invoice = $this->billing->issueInvoiceForSubscription($data['subscription_id'], isset($data['bill_at']) ? new \DateTime($data['bill_at']) : null);
        return response()->json(['data' => $invoice], Response::HTTP_CREATED);
    }
}

