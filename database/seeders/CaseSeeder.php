<?php

namespace Database\Seeders;

use App\Models\CaseModel;
use App\Models\CaseParty;
use App\Models\OseFileNumber;
use App\Models\ServiceList;
use App\Models\User;
use App\Models\Person;
use Illuminate\Database\Seeder;

class CaseSeeder extends Seeder
{
    public function run(): void
    {
        $aluClerk = User::where('role', 'alu_clerk')->first();
        $huAdmin = User::where('role', 'hu_admin')->first();
        
        // Case 1 - Draft
        $case1 = CaseModel::create([
            'case_no' => '25-001',
            'caption' => 'In the Matter of Application by John Smith for Permit to Appropriate Water from the Rio Grande',
            'case_type' => 'protested',
            'status' => 'draft',
            'created_by_user_id' => $aluClerk->id,
        ]);

        // Case 2 - Submitted to HU
        $case2 = CaseModel::create([
            'case_no' => '25-002',
            'caption' => 'In the Matter of Protest by Maria Garcia regarding Water Rights Application RG-12345',
            'case_type' => 'aggrieved',
            'status' => 'submitted_to_hu',
            'submitted_at' => now()->subDays(2),
            'created_by_user_id' => $aluClerk->id,
            'updated_by_user_id' => $huAdmin->id,
        ]);

        // Case 3 - Active
        $case3 = CaseModel::create([
            'case_no' => '25-003',
            'caption' => 'In the Matter of Compliance Action against Rio Grande Water Co for Unauthorized Water Use',
            'case_type' => 'compliance',
            'status' => 'active',
            'submitted_at' => now()->subDays(10),
            'accepted_at' => now()->subDays(8),
            'created_by_user_id' => $aluClerk->id,
            'updated_by_user_id' => $huAdmin->id,
        ]);

        // Add parties and OSE numbers for each case
        $this->addCaseData($case1, 1, 2);
        $this->addCaseData($case2, 2, 1);
        $this->addCaseData($case3, 3, 4);
    }

    private function addCaseData($case, $applicantPersonId, $protestantPersonId)
    {
        // Add parties
        CaseParty::create([
            'case_id' => $case->id,
            'role' => 'applicant',
            'person_id' => $applicantPersonId,
            'service_enabled' => true
        ]);

        CaseParty::create([
            'case_id' => $case->id,
            'role' => 'protestant',
            'person_id' => $protestantPersonId,
            'service_enabled' => true
        ]);

        // Add OSE file numbers
        OseFileNumber::create([
            'case_id' => $case->id,
            'basin_code' => 'RG',
            'file_no_from' => '12345',
            'file_no_to' => '12350'
        ]);

        // Add service list
        $persons = Person::whereIn('id', [$applicantPersonId, $protestantPersonId])->get();
        foreach ($persons as $person) {
            ServiceList::create([
                'case_id' => $case->id,
                'person_id' => $person->id,
                'email' => $person->email,
                'service_method' => 'email',
                'is_primary' => true
            ]);
        }
    }
}