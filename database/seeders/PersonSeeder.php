<?php

namespace Database\Seeders;

use App\Models\Person;
use Illuminate\Database\Seeder;

class PersonSeeder extends Seeder
{
    public function run(): void
    {
        $persons = [
            [
                'type' => 'individual',
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@example.com',
                'phone_mobile' => '505-555-0101',
                'address_line1' => '123 Main St',
                'city' => 'Albuquerque',
                'state' => 'NM',
                'zip' => '87101'
            ],
            [
                'type' => 'individual',
                'first_name' => 'Maria',
                'last_name' => 'Garcia',
                'email' => 'maria.garcia@example.com',
                'phone_mobile' => '505-555-0102',
                'address_line1' => '456 Oak Ave',
                'city' => 'Santa Fe',
                'state' => 'NM',
                'zip' => '87501'
            ],
            [
                'type' => 'company',
                'organization' => 'Rio Grande Water Co',
                'email' => 'info@rgwater.com',
                'phone_office' => '505-555-0103',
                'address_line1' => '789 Water St',
                'city' => 'Las Cruces',
                'state' => 'NM',
                'zip' => '88001'
            ],
            [
                'type' => 'individual',
                'first_name' => 'Robert',
                'last_name' => 'Johnson',
                'title' => 'Attorney',
                'email' => 'rjohnson@lawfirm.com',
                'phone_office' => '505-555-0104',
                'address_line1' => '321 Legal Blvd',
                'city' => 'Albuquerque',
                'state' => 'NM',
                'zip' => '87102'
            ]
        ];

        foreach ($persons as $personData) {
            Person::create($personData);
        }
    }
}