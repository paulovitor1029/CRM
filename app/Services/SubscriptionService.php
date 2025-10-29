<?php

namespace App\Services;

use App\Models\Addon;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionItem;
use App\Models\SubscriptionLog;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function create(array $data, ?string $userId = null, ?string $origin = null): Subscription
    {
        return $this->db->transaction(function () use ($data, $userId, $origin) {
            $tenant = $data['tenant_id'] ?? 'default';

            $sub = Subscription::create([
                'tenant_id' => $tenant,
                'customer_id' => $data['customer_id'],
                'status' => 'active',
                'starts_at' => $data['starts_at'] ?? now(),
                'trial_ends_at' => $data['trial_ends_at'] ?? null,
                'next_billing_at' => $data['next_billing_at'] ?? null,
                'pro_rata' => (bool) ($data['pro_rata'] ?? true),
                'courtesy_until' => $data['courtesy_until'] ?? null,
                'limits' => $data['limits'] ?? null,
            ]);

            $items = $data['items'] ?? [];
            // If plan_id provided, include as an item with product price
            if (!empty($data['plan_id'])) {
                $plan = Plan::findOrFail($data['plan_id']);
                $prod = $plan->product;
                $items[] = [
                    'item_type' => 'plan',
                    'item_id' => $plan->id,
                    'quantity' => 1,
                    'price_cents' => $prod->price_cents,
                    'currency' => $prod->currency,
                ];
            }

            foreach ($items as $it) {
                $qty = (int) ($it['quantity'] ?? 1);
                $price = $it['price_cents'] ?? null;
                $currency = $it['currency'] ?? 'BRL';
                if ($price === null) {
                    // derive price from referenced item
                    if ($it['item_type'] === 'product') {
                        $ref = Product::findOrFail($it['item_id']);
                        $price = $ref->price_cents; $currency = $ref->currency;
                    } elseif ($it['item_type'] === 'addon') {
                        $ref = Addon::findOrFail($it['item_id']);
                        $prod = $ref->product; $price = $prod->price_cents; $currency = $prod->currency;
                    } elseif ($it['item_type'] === 'plan') {
                        $ref = Plan::findOrFail($it['item_id']);
                        $prod = $ref->product; $price = $prod->price_cents; $currency = $prod->currency;
                    }
                }
                SubscriptionItem::create([
                    'subscription_id' => $sub->id,
                    'item_type' => $it['item_type'],
                    'item_id' => $it['item_id'],
                    'quantity' => $qty,
                    'price_cents' => $price,
                    'currency' => $currency,
                ]);
            }

            $snapshot = $this->snapshot($sub);
            SubscriptionLog::create([
                'subscription_id' => $sub->id,
                'action' => 'create',
                'before' => null,
                'after' => $snapshot,
                'user_id' => $userId,
                'origin' => $origin,
            ]);

            Log::info('subscription_created', [
                'subscription_id' => $sub->id,
                'tenant_id' => $tenant,
                'customer_id' => $sub->customer_id,
                'user_id' => $userId,
                'origin' => $origin,
            ]);

            return $sub->load('items');
        });
    }

    private function snapshot(Subscription $sub): array
    {
        $sub->loadMissing('items');
        return [
            'subscription' => $sub->only(['id','tenant_id','customer_id','status','starts_at','trial_ends_at','next_billing_at','courtesy_until','pro_rata','limits']),
            'items' => $sub->items->map->only(['item_type','item_id','quantity','price_cents','currency'])->values()->all(),
        ];
    }
}

