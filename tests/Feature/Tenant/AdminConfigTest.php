<?php

use App\Models\TenantConfig;

it('sets org branding config with versioning and logs', function () {
    $resp = $this->postJson('/api/admin/configs/branding?organization_id=default', [
        'data' => [ 'logo_key' => 'logos/acme.png', 'primary_color' => '#FF0000', 'secondary_color' => '#00FF00' ],
    ])->assertOk();
    $cfg = $resp->json('data');
    expect($cfg['version'])->toBe(1);

    $resp2 = $this->postJson('/api/admin/configs/branding?organization_id=default', [
        'data' => [ 'logo_key' => 'logos/acme2.png', 'primary_color' => '#112233', 'secondary_color' => '#445566' ],
    ])->assertOk();
    expect($resp2->json('data.version'))->toBe(2);
});

it('manages feature flags and custom fields', function () {
    $this->postJson('/api/admin/feature-flags?organization_id=default', [ 'flag_key' => 'billing', 'enabled' => true ])->assertOk();
    $this->postJson('/api/admin/custom-fields?organization_id=default', [
        'entity' => 'customers', 'name' => 'Origem', 'key' => 'origem', 'type' => 'string', 'required' => false,
    ])->assertCreated();
    $list = $this->getJson('/api/admin/custom-fields?organization_id=default&entity=customers')->assertOk();
    expect(count($list->json('data')))->toBeGreaterThan(0);
});
