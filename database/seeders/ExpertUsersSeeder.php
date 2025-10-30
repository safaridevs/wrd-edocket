<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ExpertUsersSeeder extends Seeder
{
    public function run(): void
    {
        $experts = [
            // Hydrology Experts
            ['name' => 'Dr. Sarah Waters', 'email' => 'sarah.waters@ose.nm.gov', 'role' => 'hydrology_expert', 'initials' => 'SW'],
            ['name' => 'Dr. Michael Rivers', 'email' => 'michael.rivers@ose.nm.gov', 'role' => 'hydrology_expert', 'initials' => 'MR'],
            ['name' => 'Dr. Lisa Streams', 'email' => 'lisa.streams@ose.nm.gov', 'role' => 'hydrology_expert', 'initials' => 'LS'],
            
            // WRD Experts
            ['name' => 'John Martinez', 'email' => 'john.martinez@ose.nm.gov', 'role' => 'wrd', 'initials' => 'JM'],
            ['name' => 'Maria Garcia', 'email' => 'maria.garcia@ose.nm.gov', 'role' => 'wrd', 'initials' => 'MG'],
            ['name' => 'Robert Johnson', 'email' => 'robert.johnson@ose.nm.gov', 'role' => 'wrd', 'initials' => 'RJ'],
            
            // ALU Clerks
            ['name' => 'Jennifer Smith', 'email' => 'jennifer.smith@ose.nm.gov', 'role' => 'alu_clerk', 'initials' => 'JS'],
            ['name' => 'David Brown', 'email' => 'david.brown@ose.nm.gov', 'role' => 'alu_clerk', 'initials' => 'DB'],
            ['name' => 'Amanda Wilson', 'email' => 'amanda.wilson@ose.nm.gov', 'role' => 'alu_clerk', 'initials' => 'AW'],
            
            // ALU Attorneys
            ['name' => 'Sarah Johnson', 'email' => 'sarah.johnson@ose.nm.gov', 'role' => 'alu_atty', 'initials' => 'SJ'],
            ['name' => 'Michael Davis', 'email' => 'michael.davis@ose.nm.gov', 'role' => 'alu_atty', 'initials' => 'MD'],
            ['name' => 'Lisa Rodriguez', 'email' => 'lisa.rodriguez@ose.nm.gov', 'role' => 'alu_atty', 'initials' => 'LR'],
        ];

        foreach ($experts as $expert) {
            User::create([
                'name' => $expert['name'],
                'email' => $expert['email'],
                'password' => Hash::make('password'),
                'role' => $expert['role'],
                'initials' => $expert['initials'],
                'is_active' => true,
            ]);
        }
    }
}