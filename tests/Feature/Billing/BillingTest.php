<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionItem;
use Illuminate\Support\Facades\Hash;

it('issues a monthly invoice for a subscription', function () {
    // Product and Plan
    $prod = Product::create(['tenant_id' => 'default', 'name' => 'Produto M', 'sku' => 'SKU-M', 'price_cents' => 10000, 'currency' => 'BRL']);
    $plan = Plan::create(['tenant_id' => 'default', 'product_id' => $prod->id, 'name' => 'Mensal', 'billing_interval' => 'month', 'billing_period' => 1, 'pro_rata' => true]);
    $cust = Customer::create(['tenant_id' => 'default', 'name' => 'Cliente A', 'status' => 'ativo']);
    $sub = Subscription::create(['tenant_id' => 'default', 'customer_id' => $cust->id, 'status' => 'active', 'starts_at' => now()->startOfMonth()]);
    SubscriptionItem::create(['subscription_id' => $sub->id, 'item_type' => 'plan', 'item_id' => $plan->id, 'quantity' => 1, 'price_cents' => $prod->price_cents, 'currency' => 'BRL']);

    $inv = $this->postJson('/api/invoices', ['subscription_id' => $sub->id])->assertCreated()->json('data');
    expect($inv['total_cents'])->toBe(10000);
});

it('applies courtesy to make invoice free', function () {
    $prod = Product::create(['tenant_id' => 'default', 'name' => 'Produto C', 'sku' => 'SKU-C', 'price_cents' => 15000, 'currency' => 'BRL']);
    $plan = Plan::create(['tenant_id' => 'default', 'product_id' => $prod->id, 'name' => 'Mensal', 'billing_interval' => 'month', 'billing_period' => 1, 'pro_rata' => true]);
    $cust = Customer::create(['tenant_id' => 'default', 'name' => 'Cliente B', 'status' => 'ativo']);
    $sub = Subscription::create(['tenant_id' => 'default', 'customer_id' => $cust->id, 'status' => 'active', 'starts_at' => now()->startOfMonth(), 'courtesy_until' => now()->endOfMonth()]);
    SubscriptionItem::create(['subscription_id' => $sub->id, 'item_type' => 'plan', 'item_id' => $plan->id, 'quantity' => 1, 'price_cents' => $prod->price_cents, 'currency' => 'BRL']);

    $inv = $this->postJson('/api/invoices', ['subscription_id' => $sub->id])->assertCreated()->json('data');
    expect($inv['total_cents'])->toBe(0);
});

it('records a payment and emits PaymentApproved to outbox', function () {
    $prod = Product::create(['tenant_id' => 'default', 'name' => 'Produto P', 'sku' => 'SKU-P', 'price_cents' => 12000, 'currency' => 'BRL']);
    $plan = Plan::create(['tenant_id' => 'default', 'product_id' => $prod->id, 'name' => 'Mensal', 'billing_interval' => 'month', 'billing_period' => 1, 'pro_rata' => true]);
    $cust = Customer::create(['tenant_id' => 'default', 'name' => 'Cliente C', 'status' => 'ativo']);
    $sub = Subscription::create(['tenant_id' => 'default', 'customer_id' => $cust->id, 'status' => 'active', 'starts_at' => now()->startOfMonth()]);
    SubscriptionItem::create(['subscription_id' => $sub->id, 'item_type' => 'plan', 'item_id' => $plan->id, 'quantity' => 1, 'price_cents' => $prod->price_cents, 'currency' => 'BRL']);
    $inv = $this->postJson('/api/invoices', ['subscription_id' => $sub->id])->assertCreated()->json('data');

    $this->postJson('/api/payments', [
        'invoice_id' => $inv['id'],
        'status' => 'paid',
        'amount_cents' => 12000,
        'currency' => 'BRL',
        'method' => 'pix',
    ])->assertCreated();

    $this->assertDatabaseHas('outbox', [ 'event_key' => 'payment.approved' ]);
});

