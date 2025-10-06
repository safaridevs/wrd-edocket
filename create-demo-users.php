<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "Creating demo users for OSE E-Docket...\n\n";

// Use existing valid roles only
$users = [
    ['name' => 'Sarah Johnson', 'email' => 'sarah.johnson@ose.nm.gov', 'role' => 'alu_clerk', 'initials' => 'SJ'],
    ['name' => 'Michael Rodriguez', 'email' => 'michael.rodriguez@ose.nm.gov', 'role' => 'alu_mgr', 'initials' => 'MR'],
    ['name' => 'Jennifer Chen', 'email' => 'jennifer.chen@ose.nm.gov', 'role' => 'alu_atty', 'initials' => 'JC'],
    ['name' => 'David Thompson', 'email' => 'david.thompson@ose.nm.gov', 'role' => 'hu_admin', 'initials' => 'DT'],
    ['name' => 'Lisa Martinez', 'email' => 'lisa.martinez@ose.nm.gov', 'role' => 'hu_clerk', 'initials' => 'LM'],
    ['name' => 'Dr. Robert Wilson', 'email' => 'robert.wilson@ose.nm.gov', 'role' => 'wrd', 'initials' => 'RW'],
    ['name' => 'John Smith', 'email' => 'john.smith@email.com', 'role' => 'party', 'initials' => 'JS'],
    ['name' => 'Maria Garcia', 'email' => 'maria.garcia@email.com', 'role' => 'party', 'initials' => 'MG'],
    ['name' => 'ABC Ranch LLC', 'email' => 'contact@abcranch.com', 'role' => 'party', 'initials' => 'AR'],
];

foreach ($users as $userData) {
    try {
        $user = User::firstOrCreate(
            ['email' => $userData['email']],
            array_merge($userData, ['password' => Hash::make('password123')])
        );
        echo "✓ Created user: {$userData['name']} ({$userData['role']})\n";
    } catch (Exception $e) {
        echo "✗ Failed to create {$userData['name']}: " . $e->getMessage() . "\n";
    }
}

echo "\n========================================\n";
echo "Demo users created successfully!\n";
echo "All passwords are: password123\n\n";
echo "Login Credentials:\n";
echo "ALU Clerk: sarah.johnson@ose.nm.gov\n";
echo "ALU Manager: michael.rodriguez@ose.nm.gov\n";
echo "ALU Attorney: jennifer.chen@ose.nm.gov\n";
echo "HU Admin: david.thompson@ose.nm.gov\n";
echo "HU Clerk: lisa.martinez@ose.nm.gov\n";
echo "WRD Expert: robert.wilson@ose.nm.gov\n";
echo "Party 1: john.smith@email.com\n";
echo "Party 2: maria.garcia@email.com\n";
echo "Party 3: contact@abcranch.com\n";
echo "========================================\n";