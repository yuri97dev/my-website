<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemAdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    private const NAME = "YURI SEABRA DA SILVA";
    private const EMAIL = "yuri97real@gmail.com";

    /**
     * Seed the initial system administrator user.
     */
    public function run(): void
    {
        $password = config("seeding.default_password");

        if( blank($password) ) {
            $this->command?->warn("SEEDING_PASSWORD não definido.");
            return;
        }

        $user = User::query()->firstOrCreate(
            ["email" => self::EMAIL],
            [
                "name" => self::NAME,
                "password" => $password,
                "email_verified_at" => now(),
            ],
        );

        $system_admin_role = Role::query()
            ->where("slug", Role::SYSTEM_ADMIN)
            ->firstOrFail();

        $user->roles()->syncWithoutDetaching([$system_admin_role->id]);
    }
}
