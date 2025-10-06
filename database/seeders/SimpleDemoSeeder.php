<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SimpleDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo users only
        $users = [
            // ALU Staff
            ['name' => 'Sarah Johnson', 'email' => 'sarah.johnson@ose.nm.gov', 'role' => 'alu_clerk', 'initials' => 'SJ'],
            ['name' => 'Michael Rodriguez', 'email' => 'michael.rodriguez@ose.nm.gov', 'role' => 'alu_mgr', 'initials' => 'MR'],
            ['name' => 'Jennifer Chen', 'email' => 'jennifer.chen@ose.nm.gov', 'role' => 'alu_atty', 'initials' => 'JC'],
            
            // HU Staff
            ['name' => 'David Thompson', 'email' => 'david.thompson@ose.nm.gov', 'role' => 'hu_admin', 'initials' => 'DT'],
            ['name' => 'Lisa Martinez', 'email' => 'lisa.martinez@ose.nm.gov', 'role' => 'hu_clerk', 'initials' => 'LM'],
            
            // Hydrology Expert
            ['name' => 'Dr. Robert Wilson', 'email' => 'robert.wilson@ose.nm.gov', 'role' => 'hydrology_expert', 'initials' => 'RW'],
            
            // Parties
            ['name' => 'John Smith', 'email' => 'john.smith@email.com', 'role' => 'party', 'initials' => 'JS'],
            ['name' => 'Maria Garcia', 'email' => 'maria.garcia@email.com', 'role' => 'party', 'initials' => 'MG'],
            ['name' => 'ABC Ranch LLC', 'email' => 'contact@abcranch.com', 'role' => 'party', 'initials' => 'AR'],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, ['password' => Hash::make('password123')])
            );
        }

        $this->command->info('Demo users created successfully!');
        $this->command->info('All passwords are: password123');
    }
}