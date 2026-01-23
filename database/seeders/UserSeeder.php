<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Compte Admin
        User::updateOrCreate(
            ['email' => 'dev@ollaid.com'],
            [
                'name' => 'Pape Ndiouga',
                'email' => 'dev@ollaid.com',
                'password' => Hash::make('admin123'),
                'ccphone' => '+221',
                'phone' => '786080939',
                'account_type' => 'admin',
                'reference' => 'AD001',
                'email_verified_at' => now(),
            ]
        );

        // Compte Client
        User::updateOrCreate(
            ['email' => 'ollaidpn@gmail.com'],
            [
                'name' => 'Omar Sané',
                'email' => 'ollaidpn@gmail.com',
                'password' => Hash::make('admin123'),
                'ccphone' => '+221',
                'phone' => '778704565',
                'account_type' => 'client',
                'reference' => 'CL001',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✅ Utilisateurs admin et client créés avec succès!');
        $this->command->info('   Admin: dev@ollaid.com / admin123');
        $this->command->info('   Client: ollaidpn@gmail.com / admin123');
    }
}
