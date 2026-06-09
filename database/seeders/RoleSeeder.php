<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's role catalog.
     */
    public function run(): void
    {
        Role::query()->firstOrCreate(
            ["slug" => Role::SYSTEM_ADMIN],
            ["name" => "Administrador do Sistema"],
        );
    }
}
