<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,
            RbacSeeder::class,
            CustomerStatusSeeder::class,
            SlaPolicySeeder::class,
        ]);
    }
}

