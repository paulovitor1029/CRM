<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CustomerStatusSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(CustomerStatusSeeder::class);
});

it('creates product with metadata and lists it', function () {
    $this->actingAs(User::factory()->create(['password' => Hash::make('Str0ngP@ssw0rd!')]));
    $resp = $this->postJson('/api/products', [
        'name' => 'Produto X',
        'sku' => 'SKU-X',
        'price_cents' => 9900,
        'currency' => 'BRL',
        'metadata' => ['features' => ['a','b']],
    ])->assertCreated();
    $id = $resp->json('data.id');

    $list = $this->getJson('/api/products')->assertOk();
    expect(collect($list->json('data'))->firstWhere('id', $id))->not->toBeNull();
});

it('creates plan for product and subscription for customer', function () {
    $this->actingAs(User::factory()->create());
    // Product
    $prod = Product::create(['tenant_id' => 'default', 'name' => 'Produto Y', 'sku' => 'SKU-Y', 'price_cents' => 15000, 'currency' => 'BRL']);
    // Plan via API
    $plan = $this->postJson('/api/plans', [
        'product_id' => $prod->id,
        'name' => 'Plano Mês',
        'billing_interval' => 'month',
        'billing_period' => 1,
        'trial_days' => 14,
        'pro_rata' => true,
        'courtesy_days' => 0,
    ])->assertCreated()->json('data');

    // Customer
    $cust = Customer::create(['tenant_id' => 'default', 'name' => 'Cliente Z', 'status' => 'ativo']);

    // Subscription
    $sub = $this->postJson('/api/subscriptions', [
        'customer_id' => $cust->id,
        'plan_id' => $plan['id'],
        'pro_rata' => true,
    ])->assertCreated();

    $sub->assertJsonPath('data.status', 'active');
    $this->assertDatabaseHas('subscription_items', ['subscription_id' => $sub->json('data.id'), 'item_type' => 'plan']);
});

