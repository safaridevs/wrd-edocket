<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AluAttorneySeeder extends Seeder
{
    public function run(): void
    {
        $attorneys = [
            ['name' => 'Sarah Johnson', 'email' => 'sarah.johnson@ose.nm.gov', 'role' => 'alu_atty', 'initials' => 'SJ'],
            ['name' => 'Michael Davis', 'email' => 'michael.davis@ose.nm.gov', 'role' => 'alu_atty', 'initials' => 'MD'],
            ['name' => 'Lisa Rodriguez', 'email' => 'lisa.rodriguez@ose.nm.gov', 'role' => 'alu_atty', 'initials' => 'LR'],
        ];

        foreach ($attorneys as $attorney) {
            User::firstOrCreate(
                ['email' => $attorney['email']],
                [
                    'name' => $attorney['name'],
                    'password' => Hash::make('password'),
                    'role' => $attorney['role'],
                    'initials' => $attorney['initials'],
                    'is_active' => true,
                ]
            );
        }
    }
}