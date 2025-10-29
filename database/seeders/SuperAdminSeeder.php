<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'superadmin'], ['label' => 'Super Administrator']);
        $user = User::firstOrCreate(['email' => 'admin@system.local'], [
            'name' => 'System Admin',
            'password' => Hash::make('admin'),
        ]);
        // attach role
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}

