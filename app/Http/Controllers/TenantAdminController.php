<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use App\Models\TenantConfig;
use App\Models\TenantCustomField;
use App\Models\TenantFeatureFlag;
use App\Services\TenantAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantAdminController
{
    public function __construct(private readonly TenantAdminService $svc) {}

    // Configs by scope
    public function getConfigs(Request $request): JsonResponse
    {
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $rows = TenantConfig::where('tenant_id',$tenant)->get();
        return response()->json(['data' => $rows]);
    }

    public function setConfig(string $scope, Request $request): JsonResponse
    {
        $data = $request->validate(['data' => ['required','array']]);
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $cfg = $this->svc->setConfig($tenant, $scope, $data['data'], optional($request->user())->id);
        return response()->json(['data' => $cfg]);
    }

    // Custom fields
    public function listFields(Request $request): JsonResponse
    {
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $entity = $request->query('entity');
        $q = TenantCustomField::where('tenant_id',$tenant);
        if ($entity) $q->where('entity',$entity);
        return response()->json(['data' => $q->orderBy('order')->get()]);
    }

    public function upsertField(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'entity' => ['required','string','max:64'],
            'name' => ['required','string','max:255'],
            'key' => ['required','string','max:64'],
            'type' => ['required','in:string,number,boolean,date,enum'],
            'required' => ['boolean'],
            'visibility_roles' => ['array'],
            'options' => ['array'],
            'order' => ['integer'],
            'active' => ['boolean'],
        ]);
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $field = $this->svc->upsertCustomField($tenant, $payload + ['tenant_id'=>$tenant] , optional($request->user())->id);
        return response()->json(['data' => $field], Response::HTTP_CREATED);
    }

    // Feature flags
    public function flags(Request $request): JsonResponse
    {
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $rows = TenantFeatureFlag::where('tenant_id',$tenant)->get();
        return response()->json(['data' => $rows]);
    }

    public function setFlag(Request $request): JsonResponse
    {
        $data = $request->validate(['flag_key' => ['required','string','max:128'], 'enabled' => ['required','boolean']]);
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $flag = $this->svc->setFeatureFlag($tenant, $data['flag_key'], $data['enabled'], optional($request->user())->id);
        return response()->json(['data' => $flag]);
    }

    // Templates
    public function templates(Request $request): JsonResponse
    {
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $q = MessageTemplate::where('tenant_id',$tenant);
        if ($request->query('channel')) $q->where('channel',$request->query('channel'));
        return response()->json(['data' => $q->get()]);
    }

    public function upsertTemplate(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'channel' => ['required','in:push,email,wa'],
            'key' => ['required','string','max:128'],
            'subject' => ['nullable','string','max:255'],
            'body' => ['required','string'],
        ]);
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $tpl = $this->svc->upsertTemplate($tenant, $payload + ['tenant_id'=>$tenant], optional($request->user())->id);
        return response()->json(['data' => $tpl], Response::HTTP_CREATED);
    }
}

