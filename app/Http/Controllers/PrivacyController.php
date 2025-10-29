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
            'organization_id' => ['nullable','string','max:64'],
            'subject_type' => ['required','string','max:64'],
            'subject_id' => ['required','string','max:64'],
            'purpose' => ['required','string','max:128'],
            'version' => ['nullable','string','max:64'],
        ]);
        $rec = PrivacyConsent::create([
            'organization_id' => $data['organization_id'] ?? (string) ($request->attributes->get('organization_id') ?? $request->query('organization_id') ?? 'default'),
            'subject_type' => $data['subject_type'],
            'subject_id' => $data['subject_id'],
            'purpose' => $data['purpose'],
            'version' => $data['version'] ?? null,
            'given_at' => now(),
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
        $this->privacy->logAccess($rec->organization_id, $rec->subject_type, $rec->subject_id, 'consent', [
            'resource' => 'privacy_consents', 'resource_id' => $rec->id, 'ip' => $request->ip(), 'user_agent' => (string) $request->userAgent(), 'actor_type' => 'user', 'actor_id' => optional($request->user())->id,
        ]);
        return response()->json(['data' => $rec], Response::HTTP_CREATED);
    }

    public function revokeConsent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'organization_id' => ['nullable','string','max:64'],
            'subject_type' => ['required','string','max:64'],
            'subject_id' => ['required','string','max:64'],
            'purpose' => ['required','string','max:128'],
        ]);
        $rec = PrivacyConsent::where('organization_id', $data['organization_id'] ?? (string) ($request->attributes->get('organization_id') ?? $request->query('organization_id') ?? 'default'))
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
            'organization_id' => ['nullable','string','max:64'],
            'subject_type' => ['required','string','max:64'],
            'subject_id' => ['required','string','max:64'],
        ]);
        $tenant = $data['organization_id'] ?? (string) ($request->attributes->get('organization_id') ?? $request->query('organization_id') ?? 'default');
        $logs = AccessLog::where('organization_id', $tenant)
            ->where('subject_type', $data['subject_type'])
            ->where('subject_id', $data['subject_id'])
            ->orderByDesc('occurred_at')->paginate(50);
        $consents = PrivacyConsent::where('organization_id',$tenant)
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
        $org = (string) ($request->attributes->get('organization_id') ?? $request->query('organization_id') ?? 'default');
        $this->privacy->logAccess($org, $data['subject_type'], $data['subject_id'], 'anonymize', [ 'actor_type' => 'user', 'actor_id' => optional($request->user())->id ]);
        return response()->json(['message' => 'Anonymized']);
    }
}
