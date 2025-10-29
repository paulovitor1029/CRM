<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\UserNotificationPref;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $list = Notification::query()
            ->where('tenant_id', $tenant)
            ->where(function ($q) use ($user) {
                $q->whereNull('user_id');
                if ($user) { $q->orWhere('user_id', $user->id); }
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
        return response()->json(['data' => $list]);
    }

    public function markRead(string $id, Request $request): JsonResponse
    {
        $n = Notification::findOrFail($id);
        $n->read_at = now();
        $n->save();
        return response()->json(['data' => $n]);
    }

    public function saveSubscription(Request $request): JsonResponse
    {
        $user = $request->user();
        $payload = $request->validate([
            'endpoint' => ['required', 'string'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'browser' => ['nullable', 'string'],
            'platform' => ['nullable', 'string'],
        ]);
        $prefs = UserNotificationPref::firstOrNew(['user_id' => $user->id]);
        $subs = $prefs->push_subscriptions ?? [];
        $subs[] = $payload;
        $prefs->push_subscriptions = $subs;
        $prefs->save();
        return response()->json(['message' => 'Subscription saved'], Response::HTTP_CREATED);
    }
}

