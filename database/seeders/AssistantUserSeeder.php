<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssistantUserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::query()->find(User::ASSISTANT_ID);

        if( !$user ) {
            User::unguard();

            User::query()->create([
                "id" => User::ASSISTANT_ID,
                "name" => User::ASSISTANT_NAME,
                "email" => User::ASSISTANT_EMAIL,
                "password" => Str::password(64),
                "email_verified_at" => now(),
            ]);

            User::reguard();

            $this->resetUsersIdSequence();
            return;
        }

        $user->update([
            "name" => User::ASSISTANT_NAME,
            "email" => User::ASSISTANT_EMAIL,
        ]);
    }

    private function resetUsersIdSequence(): void
    {
        DB::statement(
            "SELECT setval(pg_get_serial_sequence('users', 'id'), (SELECT GREATEST(MAX(id), 1) FROM users))",
        );
    }
}
