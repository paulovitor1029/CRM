<?php

namespace App\Http\Controllers;

use App\Models\PrivacyConsent;
use App\Models\AccessLog;
use App\Services\PrivacyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrivacyController
{
    public function __construct(private readonly PrivacyService $privacy)
    {
    }

    public function consents(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['nullable','string','max:64'],
            'subject_type' => ['required','string','max:64'],
            'subject_id' => ['required','string','max:64'],
            'purpose' => ['required','string','max:128'],
            'version' => ['nullable','string','max:64'],
        ]);
        $rec = PrivacyConsent::create([
            'tenant_id' => $data['tenant_id'] ?? 'default',
            'subject_type' => $data['subject_type'],
            'subject_id' => $data['subject_id'],
            'purpose' => $data['purpose'],
            'version' => $data['version'] ?? null,
            'given_at' => now(),
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
        $this->privacy->logAccess($rec->tenant_id, $rec->subject_type, $rec->subject_id, 'consent', [
            'resource' => 'privacy_consents', 'resource_id' => $rec->id, 'ip' => $request->ip(), 'user_agent' => (string) $request->userAgent(), 'actor_type' => 'user', 'actor_id' => optional($request->user())->id,
        ]);
        return response()->json(['data' => $rec], Response::HTTP_CREATED);
    }

    public function revokeConsent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['nullable','string','max:64'],
            'subject_type' => ['required','string','max:64'],
            'subject_id' => ['required','string','max:64'],
            'purpose' => ['required','string','max:128'],
        ]);
        $rec = PrivacyConsent::where('tenant_id', $data['tenant_id'] ?? 'default')
            ->where('subject_type', $data['subject_type'])
            ->where('subject_id', $data['subject_id'])
            ->where('purpose', $data['purpose'])
            ->orderByDesc('given_at')->firstOrFail();
        $rec->revoked_at = now();
        $rec->save();
        return response()->json(['data' => $rec]);
    }

    public function accessReport(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['nullable','string','max:64'],
            'subject_type' => ['required','string','max:64'],
            'subject_id' => ['required','string','max:64'],
        ]);
        $tenant = $data['tenant_id'] ?? 'default';
        $logs = AccessLog::where('tenant_id', $tenant)
            ->where('subject_type', $data['subject_type'])
            ->where('subject_id', $data['subject_id'])
            ->orderByDesc('occurred_at')->paginate(50);
        $consents = PrivacyConsent::where('tenant_id',$tenant)
            ->where('subject_type', $data['subject_type'])
            ->where('subject_id', $data['subject_id'])
            ->orderByDesc('given_at')->get();
        return response()->json(['logs' => $logs->items(), 'consents' => $consents, 'meta' => ['current_page' => $logs->currentPage()]]);
    }

    public function anonymize(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject_type' => ['required','string','in:user,customer'],
            'subject_id' => ['required','string','max:64'],
        ]);
        $this->privacy->anonymize($data['subject_type'], $data['subject_id']);
        $this->privacy->logAccess('default', $data['subject_type'], $data['subject_id'], 'anonymize', [ 'actor_type' => 'user', 'actor_id' => optional($request->user())->id ]);
        return response()->json(['message' => 'Anonymized']);
    }
}

