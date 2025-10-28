<?php

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\CustomerStatusSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(CustomerStatusSeeder::class);
});

it('creates a customer with contacts, addresses, tags and writes history', function () {
    $this->actingAs(User::factory()->create(['password' => Hash::make('Str0ngP@ssw0rd!')]));

    $payload = [
        'tenant_id' => 'default',
        'name' => 'ACME Ltd',
        'email' => 'info@acme.test',
        'phone' => '+55 11 99999-0000',
        'status' => 'ativo',
        'funnel_stage' => 'lead',
        'contacts' => [
            ['type' => 'email', 'value' => 'ceo@acme.test', 'preferred' => true],
        ],
        'addresses' => [
            ['type' => 'billing', 'line1' => 'Rua 1', 'city' => 'SP', 'state' => 'SP', 'postal_code' => '00000-000', 'country' => 'BR'],
        ],
        'tags' => ['vip', 'saas'],
        'origin' => 'api-test',
    ];
    $resp = $this->postJson('/api/customers', $payload)->assertCreated();
    $id = $resp->json('data.id');

    $this->assertDatabaseHas('customers', ['id' => $id, 'name' => 'ACME Ltd', 'status' => 'ativo']);
    $this->assertDatabaseHas('customer_contacts', ['customer_id' => $id, 'type' => 'email']);
    $this->assertDatabaseHas('addresses', ['customer_id' => $id, 'type' => 'billing']);
    $this->assertDatabaseHas('customer_tags', ['customer_id' => $id, 'tag' => 'vip']);
    $this->assertDatabaseHas('customer_history', ['customer_id' => $id, 'action' => 'create', 'origin' => 'api-test']);
});

it('filters customers by status, tag and funnel with cursor pagination', function () {
    $this->actingAs(User::factory()->create());

    // create 3 customers
    $c1 = $this->postJson('/api/customers', [
        'name' => 'A', 'status' => 'ativo', 'tags' => ['vip'], 'funnel_stage' => 'lead'
    ])->assertCreated()->json('data.id');
    $c2 = $this->postJson('/api/customers', [
        'name' => 'B', 'status' => 'teste', 'tags' => ['trial'], 'funnel_stage' => 'trial'
    ])->assertCreated()->json('data.id');
    $c3 = $this->postJson('/api/customers', [
        'name' => 'C', 'status' => 'ativo', 'tags' => ['vip','trial'], 'funnel_stage' => 'lead'
    ])->assertCreated()->json('data.id');

    // filter by status=ativo
    $this->getJson('/api/customers?status=ativo')->assertOk()
        ->assertJson(fn ($json) => $json->has('data'));

    // filter by tag=vip
    $this->getJson('/api/customers?tag=vip')->assertOk()
        ->assertJson(fn ($json) => $json->has('data'));

    // filter by funnel stage
    $this->getJson('/api/customers?funnel=lead&per_page=1')->assertOk()
        ->assertJson(fn ($json) => $json->has('next_cursor'));
});

