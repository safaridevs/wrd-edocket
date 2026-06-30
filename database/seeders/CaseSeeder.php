<?php

namespace Database\Seeders;

use App\Models\CaseModel;
use App\Models\CaseParty;
use App\Models\OseFileNumber;
use App\Models\Person;
use App\Models\ServiceList;
use App\Models\User;
use Illuminate\Database\Seeder;

class CaseSeeder extends Seeder
{
    public function run(): void
    {
        $aluClerk = User::whereCurrentRole('alu_clerk')->firstOrFail();
        $huAdmin = User::whereCurrentRole('hu_admin')->firstOrFail();

        $cases = [
            [
                'case' => [
                    'case_no' => '25-001',
                    'caption' => 'In the Matter of Application by John Smith for Permit to Appropriate Water from the Rio Grande',
                    'case_type' => 'protested',
                    'status' => 'draft',
                    'created_by_user_id' => $aluClerk->id,
                    'updated_by_user_id' => null,
                    'submitted_at' => null,
                    'accepted_at' => null,
                ],
                'applicant_email' => 'john.smith@example.com',
                'protestant_email' => 'maria.garcia@example.com',
            ],
            [
                'case' => [
                    'case_no' => '25-002',
                    'caption' => 'In the Matter of Protest by Maria Garcia regarding Water Rights Application RG-12345',
                    'case_type' => 'aggrieved',
                    'status' => 'submitted_to_hu',
                    'submitted_at' => now()->subDays(2),
                    'accepted_at' => null,
                    'created_by_user_id' => $aluClerk->id,
                    'updated_by_user_id' => $huAdmin->id,
                ],
                'applicant_email' => 'maria.garcia@example.com',
                'protestant_email' => 'john.smith@example.com',
            ],
            [
                'case' => [
                    'case_no' => '25-003',
                    'caption' => 'In the Matter of Compliance Action against Rio Grande Water Co for Unauthorized Water Use',
                    'case_type' => 'compliance',
                    'status' => 'active',
                    'submitted_at' => now()->subDays(10),
                    'accepted_at' => now()->subDays(8),
                    'created_by_user_id' => $aluClerk->id,
                    'updated_by_user_id' => $huAdmin->id,
                ],
                'applicant_email' => 'info@rgwater.com',
                'protestant_email' => 'rjohnson@lawfirm.com',
            ],
        ];

        foreach ($cases as $seedCase) {
            $case = CaseModel::updateOrCreate(
                ['case_no' => $seedCase['case']['case_no']],
                $seedCase['case']
            );

            $applicant = Person::where('email', $seedCase['applicant_email'])->firstOrFail();
            $protestant = Person::where('email', $seedCase['protestant_email'])->firstOrFail();

            $this->addCaseData($case, $applicant, $protestant);
        }
    }

    private function addCaseData(CaseModel $case, Person $applicant, Person $protestant): void
    {
        foreach ([['applicant', $applicant], ['protestant', $protestant]] as [$role, $person]) {
            CaseParty::updateOrCreate(
                [
                    'case_id' => $case->id,
                    'role' => $role,
                    'person_id' => $person->id,
                ],
                [
                    'service_enabled' => true,
                ]
            );

            ServiceList::updateOrCreate(
                [
                    'case_id' => $case->id,
                    'person_id' => $person->id,
                ],
                [
                    'email' => $person->email,
                    'service_method' => 'email',
                    'is_primary' => true,
                ]
            );
        }

        OseFileNumber::updateOrCreate(
            [
                'case_id' => $case->id,
                'basin_code' => 'RG',
                'file_no_from' => '12345',
            ],
            [
                'file_no_to' => '12350',
            ]
        );
    }
}
