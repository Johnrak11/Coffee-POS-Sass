<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class PromoteSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if ($user) {
            $user->is_super_admin = true;
            $user->save();
            $this->command->info("User {$user->name} ({$user->email}) is now a Super Admin!");
        } else {
            $this->command->error("No users found to promote.");
        }
    }
}
