<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('presigns a file upload and stores record', function () {
    $this->actingAs(User::factory()->create(['password' => Hash::make('Str0ngP@ssw0rd!')]));
    $resp = $this->postJson('/api/files/presign', [
        'key' => 'uploads/test.txt',
        'content_type' => 'text/plain',
        'size' => 12,
    ])->assertCreated();
    expect($resp->json('key'))->toBe('uploads/test.txt');
});

