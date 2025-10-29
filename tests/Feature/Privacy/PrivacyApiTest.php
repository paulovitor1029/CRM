<?php

use App\Models\Customer;
use App\Models\PrivacyConsent;

it('records and revokes consent, and produces access report', function () {
    $cust = Customer::create(['organization_id' => 'default', 'name' => 'John', 'status' => 'ativo']);
    $c = $this->postJson('/api/privacy/consents', [
        'subject_type' => 'customer',
        'subject_id' => $cust->id,
        'purpose' => 'marketing',
    ])->assertCreated()->json('data');

    $this->postJson('/api/privacy/consents/revoke', [
        'subject_type' => 'customer',
        'subject_id' => $cust->id,
        'purpose' => 'marketing',
    ])->assertOk();

    $report = $this->getJson('/api/privacy/access-report?subject_type=customer&subject_id='.$cust->id)->assertOk();
    expect($report->json('consents.0.purpose'))->toBe('marketing');
});

it('anonymizes customer PII', function () {
    $cust = Customer::create(['organization_id' => 'default', 'name' => 'Alice', 'email' => 'alice@example.com', 'phone' => '123', 'status' => 'ativo']);
    $this->postJson('/api/privacy/anonymize', [
        'subject_type' => 'customer',
        'subject_id' => $cust->id,
    ])->assertOk();
    $cust->refresh();
    expect($cust->email)->toBeNull();
});
