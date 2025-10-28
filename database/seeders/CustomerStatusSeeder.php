<?php

namespace Database\Seeders;

use App\Models\CustomerStatus;
use Illuminate\Database\Seeder;

class CustomerStatusSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = 'default';
        $statuses = [
            ['name' => 'ativo', 'label' => 'Ativo'],
            ['name' => 'teste', 'label' => 'Teste'],
            ['name' => 'inativo', 'label' => 'Inativo'],
            ['name' => 'suspenso', 'label' => 'Suspenso'],
            ['name' => 'cancelado', 'label' => 'Cancelado'],
        ];
        foreach ($statuses as $s) {
            CustomerStatus::updateOrCreate([
                'tenant_id' => $tenant,
                'name' => $s['name'],
            ], [
                'label' => $s['label'],
                'is_active' => true,
            ]);
        }
    }
}

