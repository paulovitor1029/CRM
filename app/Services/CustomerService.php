<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\CustomerHistory;
use App\Models\CustomerTag;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;

class CustomerService
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function create(array $data, ?string $userId = null, ?string $origin = null): Customer
    {
        return $this->db->transaction(function () use ($data, $userId, $origin) {
            $tenant = $data['organization_id'] ?? 'default';
            $customer = Customer::create([
                'organization_id' => $tenant,
                'external_id' => $data['external_id'] ?? null,
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'],
                'funnel_stage' => $data['funnel_stage'] ?? null,
                'meta' => $data['meta'] ?? null,
            ]);

            foreach (($data['contacts'] ?? []) as $c) {
                CustomerContact::create([
                    'customer_id' => $customer->id,
                    'type' => $c['type'],
                    'value' => $c['value'],
                    'preferred' => (bool) ($c['preferred'] ?? false),
                    'meta' => $c['meta'] ?? null,
                ]);
            }

            foreach (($data['addresses'] ?? []) as $a) {
                Address::create([
                    'customer_id' => $customer->id,
                    'type' => $a['type'],
                    'line1' => $a['line1'],
                    'line2' => $a['line2'] ?? null,
                    'city' => $a['city'],
                    'state' => $a['state'] ?? null,
                    'postal_code' => $a['postal_code'] ?? null,
                    'country' => $a['country'] ?? 'BR',
                    'meta' => $a['meta'] ?? null,
                ]);
            }

            foreach (($data['tags'] ?? []) as $t) {
                CustomerTag::firstOrCreate([
                    'customer_id' => $customer->id,
                    'tag' => $t,
                ]);
            }

            $snapshot = $this->snapshot($customer);
            CustomerHistory::create([
                'customer_id' => $customer->id,
                'action' => 'create',
                'before' => null,
                'after' => $snapshot,
                'user_id' => $userId,
                'origin' => $origin,
            ]);

            Log::info('customer_created', [
                'customer_id' => $customer->id,
                'organization_id' => $tenant,
                'user_id' => $userId,
                'origin' => $origin,
            ]);

            return $customer->load(['contacts', 'addresses', 'tags']);
        });
    }

    private function snapshot(Customer $customer): array
    {
        $customer->loadMissing(['contacts', 'addresses', 'tags']);
        return [
            'customer' => $customer->only(['id', 'organization_id', 'external_id', 'name', 'email', 'phone', 'status', 'funnel_stage', 'meta']),
            'contacts' => $customer->contacts->map->only(['type', 'value', 'preferred', 'meta'])->values()->all(),
            'addresses' => $customer->addresses->map->only(['type', 'line1', 'line2', 'city', 'state', 'postal_code', 'country', 'meta'])->values()->all(),
            'tags' => $customer->tags->pluck('tag')->values()->all(),
        ];
    }
}
