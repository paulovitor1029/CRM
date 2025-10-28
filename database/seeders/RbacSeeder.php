<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $roles = collect([
            'admin' => 'Administrador',
            'gestor_setor' => 'Gestor de Setor',
            'agente' => 'Agente',
            'financeiro' => 'Financeiro',
            'suporte' => 'Suporte',
        ])->map(fn ($label, $name) => Role::firstOrCreate(['name' => $name], ['label' => $label]));

        $perms = collect([
            // CRUD generics for Items
            'items.read' => 'Listar/Visualizar Itens',
            'items.create' => 'Criar Itens',
            'items.update' => 'Atualizar Itens',
            'items.delete' => 'Remover Itens',
            // Reports with ABAC (sector)
            'reports.view' => 'Visualizar Relatórios por Setor',
        ])->map(fn ($label, $name) => Permission::firstOrCreate(['name' => $name], ['label' => $label]));

        // Assignments
        // admin -> all perms
        $roles['admin']->permissions()->syncWithoutDetaching($perms->pluck('id')->all());

        // gestor_setor -> read/update items, reports.view
        $roles['gestor_setor']->permissions()->syncWithoutDetaching(
            Permission::whereIn('name', ['items.read', 'items.update', 'reports.view'])->pluck('id')
        );

        // agente -> read items only
        $roles['agente']->permissions()->syncWithoutDetaching(
            Permission::whereIn('name', ['items.read'])->pluck('id')
        );

        // financeiro -> read items, reports.view
        $roles['financeiro']->permissions()->syncWithoutDetaching(
            Permission::whereIn('name', ['items.read', 'reports.view'])->pluck('id')
        );

        // suporte -> read items
        $roles['suporte']->permissions()->syncWithoutDetaching(
            Permission::whereIn('name', ['items.read'])->pluck('id')
        );
    }
}

