<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Person;
use App\Models\CaseModel;
use App\Models\CaseParty;
use App\Models\ServiceList;
use App\Models\OseFileNumber;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Basin codes are already created by migration, skip this step

        // Create Users
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
            User::updateOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, ['password' => Hash::make('password123')])
            );
        }

        // Create counsel contacts
        $attorneys = [
            ['first_name' => 'James', 'last_name' => 'Wilson', 'email' => 'jwilson@lawfirm.com', 'phone_office' => '505-555-0101'],
            ['first_name' => 'Patricia', 'last_name' => 'Davis', 'email' => 'pdavis@legalgroup.com', 'phone_office' => '505-555-0102'],
            ['first_name' => 'Robert', 'last_name' => 'Brown', 'email' => 'rbrown@waterlaw.com', 'phone_office' => '505-555-0103'],
        ];

        foreach ($attorneys as $attorneyData) {
            Person::updateOrCreate(
                ['email' => $attorneyData['email']],
                array_merge(['type' => 'individual'], $attorneyData)
            );
        }

        // Create Persons
        $persons = [
            [
                'type' => 'individual',
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@email.com',
                'phone_mobile' => '505-555-1001',
                'address_line1' => '123 Main St',
                'city' => 'Albuquerque',
                'state' => 'NM',
                'zip' => '87101'
            ],
            [
                'type' => 'individual',
                'first_name' => 'Maria',
                'last_name' => 'Garcia',
                'email' => 'maria.garcia@email.com',
                'phone_mobile' => '505-555-1002',
                'address_line1' => '456 Oak Ave',
                'city' => 'Santa Fe',
                'state' => 'NM',
                'zip' => '87501'
            ],
            [
                'type' => 'company',
                'organization' => 'ABC Ranch LLC',
                'email' => 'contact@abcranch.com',
                'phone_office' => '505-555-1003',
                'address_line1' => '789 Ranch Road',
                'city' => 'Las Cruces',
                'state' => 'NM',
                'zip' => '88001'
            ],
            [
                'type' => 'individual',
                'first_name' => 'William',
                'last_name' => 'Johnson',
                'email' => 'william.johnson@email.com',
                'phone_mobile' => '505-555-1004',
                'address_line1' => '321 River St',
                'city' => 'Roswell',
                'state' => 'NM',
                'zip' => '88201'
            ]
        ];

        foreach ($persons as $personData) {
            Person::updateOrCreate(['email' => $personData['email']], $personData);
        }

        // Create Cases with different statuses
        $cases = [
            [
                'case_no' => 'WR-2024-001',
                'case_type' => 'aggrieved',
                'caption' => 'In the Matter of Application for Permit to Appropriate Water by John Smith for Domestic and Irrigation Use from the Rio Grande',
                'status' => 'active',
                'created_by_user_id' => User::where('role', 'alu_clerk')->first()->id,
                'assigned_attorney_id' => User::where('role', 'alu_atty')->first()->id,
                'assigned_hydrology_expert_id' => User::where('role', 'hydrology_expert')->first()->id,
            ],
            [
                'case_no' => 'WR-2024-002',
                'case_type' => 'protested',
                'caption' => 'In the Matter of Protested Application by Maria Garcia for Commercial Use Water Rights from Underground Sources',
                'status' => 'active',
                'created_by_user_id' => User::where('role', 'alu_clerk')->first()->id,
                'assigned_attorney_id' => User::where('role', 'alu_atty')->first()->id,
            ],
            [
                'case_no' => 'WR-2024-003',
                'case_type' => 'compliance',
                'caption' => 'In the Matter of Compliance Action Against ABC Ranch LLC for Unauthorized Water Use',
                'status' => 'submitted_to_hu',
                'created_by_user_id' => User::where('role', 'alu_clerk')->first()->id,
            ],
            [
                'case_no' => 'WR-2024-004',
                'case_type' => 'aggrieved',
                'caption' => 'In the Matter of Application for Permit by William Johnson for Agricultural Water Rights',
                'status' => 'draft',
                'created_by_user_id' => User::where('role', 'alu_clerk')->first()->id,
            ]
        ];

        foreach ($cases as $caseData) {
            $case = CaseModel::updateOrCreate(['case_no' => $caseData['case_no']], $caseData);

            // Add OSE File Numbers
            OseFileNumber::updateOrCreate(
                [
                    'case_id' => $case->id,
                    'basin_code' => 'RG',
                    'file_no_from' => 'RG-' . str_pad((string) $case->id, 5, '0', STR_PAD_LEFT),
                ],
                [
                    'file_no_to' => null,
                ]
            );

            // Add parties to cases
            $this->addPartiesToCase($case);
        }

        $this->command->info('Demo data seeded successfully!');
    }

    private function addPartiesToCase($case)
    {
        $persons = Person::whereIn('email', [
            'john.smith@email.com',
            'maria.garcia@email.com',
            'contact@abcranch.com',
            'william.johnson@email.com',
        ])->orderBy('email')->get()->values();

        if ($persons->isEmpty()) {
            return;
        }

        $attorneys = Person::whereIn('email', [
            'jwilson@lawfirm.com',
            'pdavis@legalgroup.com',
            'rbrown@waterlaw.com',
        ])->orderBy('email')->get()->values();

        $roles = ['applicant', 'protestant', 'aggrieved_party'];
        
        for ($i = 0; $i < min(3, $persons->count()); $i++) {
            $person = $persons[$i];
            $role = $roles[$i % count($roles)];

            $clientParty = CaseParty::updateOrCreate(
                [
                    'case_id' => $case->id,
                    'person_id' => $person->id,
                    'role' => $role,
                ],
                [
                    'service_enabled' => true,
                ]
            );

            $attorney = $attorneys->get($i);

            if ($attorney) {
                CaseParty::updateOrCreate(
                    [
                        'case_id' => $case->id,
                        'person_id' => $attorney->id,
                        'role' => 'counsel',
                        'client_party_id' => $clientParty->id,
                    ],
                    [
                        'service_enabled' => true,
                        'representation_capacity' => CaseParty::CAPACITY_PRIVATE_COUNSEL,
                    ]
                );
            }

            ServiceList::updateOrCreate(
                [
                    'case_id' => $case->id,
                    'person_id' => $person->id,
                ],
                [
                    'email' => $person->email,
                    'service_method' => 'email',
                    'is_primary' => true
                ]
            );
        }
    }
}
