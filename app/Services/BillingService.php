<?php

namespace App\Services;

use App\Events\PaymentApproved;
use App\Models\Addon;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;

class BillingService
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function issueInvoiceForSubscription(string $subscriptionId, ?\DateTimeInterface $billAt = null): Invoice
    {
        $sub = Subscription::with('items')->findOrFail($subscriptionId);
        $tenant = $sub->tenant_id;
        $customerId = $sub->customer_id;
        $billDate = Carbon::instance($billAt ?? now());

        // Determine period boundaries (default monthly based on billDate)
        $periodStart = $billDate->copy()->startOfMonth();
        $periodEnd = $billDate->copy()->endOfMonth()->endOfDay();

        return $this->db->transaction(function () use ($sub, $tenant, $customerId, $periodStart, $periodEnd, $billDate) {
            $invoice = Invoice::create([
                'tenant_id' => $tenant,
                'customer_id' => $customerId,
                'subscription_id' => $sub->id,
                'status' => 'open',
                'currency' => 'BRL',
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'issued_at' => now(),
                'due_at' => now()->copy()->addDays(7),
            ]);

            $subtotal = 0; $courtesy = 0; $items = [];
            $daysIn = $periodStart->daysInMonth; // number of days in month

            foreach ($sub->items as $it) {
                $qty = $it->quantity;
                $unit = $it->price_cents;
                $total = $qty * $unit;

                // If item is plan/addon, apply pro-rata and courtesy
                if (in_array($it->item_type, ['plan','addon'])) {
                    // Pro-rata if subscription started within period and flag is true
                    if ($sub->pro_rata && $sub->starts_at) {
                        $start = Carbon::parse($sub->starts_at);
                        if ($start->betweenIncluded($periodStart, $periodEnd)) {
                            $daysActive = $periodEnd->diffInDays($start) + 1; // inclusive
                            $fraction = $daysActive / $daysIn;
                            $total = (int) round($total * $fraction);
                            $items[] = ['type' => 'prorate', 'description' => 'Proration ('.$daysActive.'/'.$daysIn.' days)', 'quantity' => 1, 'unit' => $total, 'total' => $total];
                            $subtotal += $total;
                            continue;
                        }
                    }

                    // Courtesy: if courtesy_until covers the entire period
                    if ($sub->courtesy_until && Carbon::parse($sub->courtesy_until)->greaterThanOrEqualTo($periodEnd)) {
                        // free; add courtesy credit item
                        $items[] = ['type' => 'courtesy', 'description' => 'Courtesy', 'quantity' => $qty, 'unit' => -$unit, 'total' => -($qty*$unit)];
                        $courtesy += $qty*$unit;
                        continue;
                    }
                }

                $items[] = ['type' => $it->item_type, 'description' => ucfirst($it->item_type), 'quantity' => $qty, 'unit' => $unit, 'total' => $total];
                $subtotal += $total;
            }

            foreach ($items as $row) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'type' => $row['type'],
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_price_cents' => $row['unit'],
                    'total_cents' => $row['total'],
                ]);
            }

            $invoice->subtotal_cents = $subtotal;
            $invoice->courtesy_cents = $courtesy;
            $invoice->discount_cents = 0;
            $invoice->total_cents = max(0, $subtotal - $courtesy);
            $invoice->save();

            InvoiceLog::create([
                'invoice_id' => $invoice->id,
                'action' => 'issue',
                'before' => null,
                'after' => ['subtotal' => $subtotal, 'total' => $invoice->total_cents],
            ]);

            return $invoice->load('items');
        });
    }

    public function registerPayment(string $invoiceId, array $data): Payment
    {
        return $this->db->transaction(function () use ($invoiceId, $data) {
            $invoice = Invoice::findOrFail($invoiceId);
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'status' => $data['status'],
                'amount_cents' => (int) $data['amount_cents'],
                'currency' => $data['currency'] ?? $invoice->currency,
                'method' => $data['method'] ?? null,
                'external_id' => $data['external_id'] ?? null,
                'paid_at' => ($data['status'] === 'paid') ? now() : null,
                'meta' => $data['meta'] ?? null,
            ]);

            $before = $invoice->only(['status']);
            if ($data['status'] === 'paid') {
                $invoice->status = 'paid';
            } elseif ($data['status'] === 'failed') {
                $invoice->status = 'failed';
            }
            $invoice->save();

            InvoiceLog::create([
                'invoice_id' => $invoice->id,
                'action' => 'payment',
                'before' => $before,
                'after' => $invoice->only(['status']),
            ]);

            if ($payment->status === 'paid') {
                event(new PaymentApproved($payment->id, $invoice->customer_id, $payment->amount_cents, $invoice->tenant_id));
            }

            return $payment;
        });
    }
}

