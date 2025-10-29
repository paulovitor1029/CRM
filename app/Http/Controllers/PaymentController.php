<?php

namespace App\Http\Controllers;

use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController
{
    public function __construct(private readonly BillingService $billing)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'invoice_id' => ['required','uuid','exists:invoices,id'],
            'status' => ['required','in:pending,paid,failed'],
            'amount_cents' => ['required','integer','min:0'],
            'currency' => ['nullable','string','size:3'],
            'method' => ['nullable','string','max:64'],
            'external_id' => ['nullable','string','max:255'],
            'meta' => ['array'],
        ]);

        $payment = $this->billing->registerPayment($data['invoice_id'], $data);
        return response()->json(['data' => $payment], Response::HTTP_CREATED);
    }
}

