<?php

namespace Database\Seeders;

use App\Models\SlaPolicy;
use Illuminate\Database\Seeder;

class SlaPolicySeeder extends Seeder
{
    public function run(): void
    {
        SlaPolicy::updateOrCreate([
            'organization_id' => 'default',
            'key' => 'default',
        ], [
            'name' => 'Default SLA',
            'target_response_minutes' => 60,
            'target_resolution_minutes' => 480,
            'active' => true,
        ]);
    }
}
