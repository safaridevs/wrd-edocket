<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'John WRD',
                'email' => 'wrd@example.com',
                'password' => Hash::make('password'),
                'role' => 'wrd',
                'initials' => 'JW',
                'phone' => '505-123-4567'
            ],
            [
                'name' => 'Sarah WRAP',
                'email' => 'wrap@example.com',
                'password' => Hash::make('password'),
                'role' => 'wrap_dir',
                'initials' => 'SW',
                'phone' => '505-123-4568'
            ],
            [
                'name' => 'Mike ALU Manager',
                'email' => 'alu_mgr@example.com',
                'password' => Hash::make('password'),
                'role' => 'alu_mgr',
                'initials' => 'MAM',
                'phone' => '505-123-4569'
            ],
            [
                'name' => 'Lisa ALU Clerk',
                'email' => 'alu_clerk@example.com',
                'password' => Hash::make('password'),
                'role' => 'alu_clerk',
                'initials' => 'LAC',
                'phone' => '505-123-4570'
            ],
            [
                'name' => 'David ALU Attorney',
                'email' => 'alu_atty@example.com',
                'password' => Hash::make('password'),
                'role' => 'alu_atty',
                'initials' => 'DAA',
                'phone' => '505-123-4571'
            ],
            [
                'name' => 'Rachel HU Admin',
                'email' => 'hu_admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'hu_admin',
                'initials' => 'RHA',
                'phone' => '505-123-4572'
            ],
            [
                'name' => 'Tom HU Clerk',
                'email' => 'hu_clerk@example.com',
                'password' => Hash::make('password'),
                'role' => 'hu_clerk',
                'initials' => 'THC',
                'phone' => '505-123-4573'
            ],
            [
                'name' => 'Jane Party',
                'email' => 'party@example.com',
                'password' => Hash::make('password'),
                'role' => 'party',
                'phone' => '505-123-4574'
            ],
            [
                'name' => 'System Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'initials' => 'SA',
                'phone' => '505-123-4575'
            ]
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }
    }
}