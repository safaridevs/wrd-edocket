<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\DemoDataSeeder;

class SetupDemo extends Command
{
    protected $signature = 'demo:setup';
    protected $description = 'Set up demo data for OSE E-Docket system';

    public function handle()
    {
        $this->info('Setting up demo data for OSE E-Docket system...');
        
        // Run migrations without fresh (to avoid constraint issues)
        $this->call('migrate');
        
        // Seed demo data
        $this->call('db:seed', ['--class' => DemoDataSeeder::class]);
        
        $this->info('Demo setup completed!');
        $this->newLine();
        
        // Display login credentials
        $this->info('Demo User Credentials (password: password123):');
        $this->table(
            ['Role', 'Name', 'Email'],
            [
                ['ALU Clerk', 'Sarah Johnson', 'sarah.johnson@ose.nm.gov'],
                ['ALU Manager', 'Michael Rodriguez', 'michael.rodriguez@ose.nm.gov'],
                ['ALU Attorney', 'Jennifer Chen', 'jennifer.chen@ose.nm.gov'],
                ['HU Admin', 'David Thompson', 'david.thompson@ose.nm.gov'],
                ['HU Clerk', 'Lisa Martinez', 'lisa.martinez@ose.nm.gov'],
                ['Hydrology Expert', 'Dr. Robert Wilson', 'robert.wilson@ose.nm.gov'],
                ['Party 1', 'John Smith', 'john.smith@email.com'],
                ['Party 2', 'Maria Garcia', 'maria.garcia@email.com'],
                ['Party 3', 'ABC Ranch LLC', 'contact@abcranch.com'],
            ]
        );
        
        $this->newLine();
        $this->info('Sample Cases Created:');
        $this->table(
            ['Case No', 'Status', 'Type'],
            [
                ['WR-2024-001', 'Approved (Public)', 'Aggrieved'],
                ['WR-2024-002', 'Active (Hearing)', 'Protested'],
                ['WR-2024-003', 'Submitted to HU', 'Compliance'],
                ['WR-2024-004', 'Draft', 'Aggrieved'],
            ]
        );
        
        return 0;
    }
}