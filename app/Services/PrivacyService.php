<?php

namespace App\Services;

use App\Models\AccessLog;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Address;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PrivacyService
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function logAccess(string $tenantId, string $subjectType, string $subjectId, string $action, array $context = []): void
    {
        AccessLog::create([
            'tenant_id' => $tenantId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'actor_type' => $context['actor_type'] ?? 'system',
            'actor_id' => $context['actor_id'] ?? null,
            'action' => $action,
            'resource' => $context['resource'] ?? null,
            'resource_id' => $context['resource_id'] ?? null,
            'fields' => $context['fields'] ?? null,
            'ip' => $context['ip'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'occurred_at' => now(),
        ]);
    }

    public function anonymize(string $subjectType, string $subjectId): void
    {
        $this->db->transaction(function () use ($subjectType, $subjectId) {
            if ($subjectType === 'user') {
                $user = User::findOrFail($subjectId);
                $user->name = 'Anon'.Str::random(6);
                $user->email = 'anon+'.Str::random(12).'@local';
                $user->save();
            } elseif ($subjectType === 'customer') {
                $c = Customer::findOrFail($subjectId);
                $c->name = 'Anon'.Str::random(6);
                $c->email = null;
                $c->phone = null;
                $c->save();
                CustomerContact::where('customer_id', $c->id)->update(['value' => null]);
                Address::where('customer_id', $c->id)->update(['line1' => null, 'line2' => null, 'city' => null, 'state' => null, 'postal_code' => null]);
            }
            Log::info('privacy_anonymized', ['subject_type' => $subjectType, 'subject_id' => $subjectId]);
        });
    }
}

