<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentType;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $caseCreationTypes = [
            ['name' => 'Application', 'code' => 'application', 'is_required' => true, 'is_pleading' => true, 'sort_order' => 1],
            ['name' => 'Denial Letter', 'code' => 'denial_letter', 'sort_order' => 2],
            ['name' => 'Letter Returning Application', 'code' => 'letter_returning_application', 'sort_order' => 3],
            ['name' => 'Letter Rejecting Application', 'code' => 'letter_rejecting_application', 'sort_order' => 4],
            ['name' => 'Letter(s) of Protest', 'code' => 'protest_letter', 'allows_multiple' => true, 'sort_order' => 5],
            ['name' => 'Notice(s) of Publication', 'code' => 'notice_publication', 'is_required' => true, 'allows_multiple' => true, 'sort_order' => 6],
            ['name' => 'Affidavit(s) of Publication', 'code' => 'affidavit_publication', 'allows_multiple' => true, 'sort_order' => 7],
            ['name' => 'Web posting of Notice of Publication', 'code' => 'web_posting_notice', 'sort_order' => 8],
            ['name' => 'Compliance Order', 'code' => 'compliance_order', 'allows_multiple' => true, 'sort_order' => 9],
            ['name' => 'Pre-Compliance Letter', 'code' => 'pre_compliance_letter', 'allows_multiple' => true, 'sort_order' => 10],
            ['name' => 'Compliance Letter', 'code' => 'compliance_letter', 'allows_multiple' => true, 'sort_order' => 11],
            ['name' => 'Notice of Violation', 'code' => 'notice_of_violation', 'allows_multiple' => true, 'sort_order' => 12],
            ['name' => 'Notice of Reprimand (Well Driller)', 'code' => 'notice_of_reprimand', 'allows_multiple' => true, 'sort_order' => 13],
            ['name' => 'Supporting documents', 'code' => 'supporting', 'allows_multiple' => true, 'sort_order' => 14],
            ['name' => 'Notice of Contemplated Action', 'code' => 'notice_contemplated_action', 'sort_order' => 15],
            ['name' => 'Cease and Desist Letter(s)', 'code' => 'cease_desist_letter', 'allows_multiple' => true, 'sort_order' => 16],
            ['name' => 'Others', 'code' => 'other', 'allows_multiple' => true, 'sort_order' => 17],
        ];

        $partyUploadTypes = [
            ['name' => 'Motion', 'code' => 'motion', 'sort_order' => 1],
            ['name' => 'Joint Motion', 'code' => 'joint_motion', 'sort_order' => 2],
            ['name' => 'Stipulated Motion', 'code' => 'stipulated_motion', 'sort_order' => 3],
            ['name' => 'Unopposed Motion', 'code' => 'unopposed_motion', 'sort_order' => 4],
            ['name' => 'Motion for Status Conference', 'code' => 'motion_status_conference', 'sort_order' => 5],
            ['name' => 'Motion for Scheduling Conference', 'code' => 'motion_scheduling_conference', 'sort_order' => 6],
            ['name' => 'Motion to Dismiss', 'code' => 'motion_to_dismiss', 'sort_order' => 7],
            ['name' => 'Motion for Summary Judgment', 'code' => 'motion_summary_judgment', 'sort_order' => 8],
            ['name' => 'Motion for Hearing', 'code' => 'motion_for_hearing', 'sort_order' => 9],
        ];

        // Case creation document types
        foreach ($caseCreationTypes as $type) {
            DocumentType::updateOrCreate(
                ['code' => $type['code']],
                array_merge($type, [
                    'category' => 'case_creation',
                    'allowed_roles' => ['alu_clerk', 'hu_admin', 'hu_clerk'],
                ])
            );
        }

        // Party upload document types
        foreach ($partyUploadTypes as $type) {
            DocumentType::updateOrCreate(
                ['code' => $type['code']],
                array_merge($type, [
                    'category' => 'party_upload',
                    'allowed_roles' => ['party', 'counsel'],
                ])
            );
        }

        // Pleading types (for stamping)
        DocumentType::updateOrCreate(
            ['code' => 'request_pre_hearing'],
            [
                'name' => 'Request for Pre-Hearing',
                'code' => 'request_pre_hearing',
                'category' => 'case_creation',
                'allowed_roles' => ['alu_clerk', 'hu_admin', 'hu_clerk'],
                'is_pleading' => true,
                'sort_order' => 14,
            ]
        );

        DocumentType::updateOrCreate(
            ['code' => 'request_to_docket'],
            [
                'name' => 'Request to Docket',
                'code' => 'request_to_docket',
                'category' => 'case_creation',
                'allowed_roles' => ['alu_clerk', 'hu_admin', 'hu_clerk'],
                'is_pleading' => true,
                'sort_order' => 18,
            ]
        );
    }
}