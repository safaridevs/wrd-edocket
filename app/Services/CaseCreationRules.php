<?php

namespace App\Services;

use Illuminate\Validation\Validator;

class CaseCreationRules
{
    private const PHONE_RULE = ['regex:/^\d{3}-\d{3}-\d{4}$/', 'max:12'];

    public function validationRules(?string $caseType): array
    {
        $rules = [
            'case_type' => 'required|in:aggrieved,protested,compliance',
            'caption' => 'required|string|max:1000',
            'wrd_office' => 'required|in:albuquerque,santa_fe',
            'parties' => 'required|array|min:1',
            'parties.*.role' => 'required|in:applicant,protestant,aggrieved_party,intervenor,respondent',
            'parties.*.type' => 'required|in:individual,company',
            'parties.*.representation' => 'nullable|in:self,attorney',
            'parties.*.attorney_option' => 'nullable|in:existing,new,no_attorney_yet',
            'parties.*.attorney_id' => 'nullable|exists:attorneys,id',
            'parties.*.prefix' => 'nullable|string|max:10',
            'parties.*.first_name' => 'nullable|string|max:255',
            'parties.*.middle_name' => 'nullable|string|max:255',
            'parties.*.last_name' => 'nullable|string|max:255',
            'parties.*.suffix' => 'nullable|string|max:10',
            'parties.*.organization' => 'nullable|string|max:255',
            'parties.*.title' => 'nullable|string|max:255',
            'parties.*.email' => 'required|email|max:255',
            'parties.*.phone' => ['nullable', 'string', ...self::PHONE_RULE],
            'parties.*.phone_mobile' => ['nullable', 'string', ...self::PHONE_RULE],
            'parties.*.phone_office' => ['nullable', 'string', ...self::PHONE_RULE],
            'parties.*.address_line1' => 'nullable|string|max:500',
            'parties.*.address_line2' => 'nullable|string|max:500',
            'parties.*.city' => 'nullable|string|max:100',
            'parties.*.state' => 'nullable|string|max:50',
            'parties.*.zip' => 'nullable|string|max:10',
            'parties.*.attorney_name' => 'nullable|string|max:255',
            'parties.*.attorney_email' => 'nullable|email|max:255',
            'parties.*.attorney_phone' => ['nullable', 'string', ...self::PHONE_RULE],
            'parties.*.bar_number' => 'nullable|string|max:50',
            'parties.*.attorney_address_line1' => 'nullable|string|max:500',
            'parties.*.attorney_address_line2' => 'nullable|string|max:500',
            'parties.*.attorney_city' => 'nullable|string|max:100',
            'parties.*.attorney_state' => 'nullable|string|max:50',
            'parties.*.attorney_zip' => 'nullable|string|max:10',
            'ose_numbers' => 'nullable|array',
            'ose_numbers.*.basin_code_from' => 'nullable|string|exists:ose_basin_codes,initial',
            'ose_numbers.*.basin_code_to' => 'nullable|string|exists:ose_basin_codes,initial',
            'ose_numbers.*.file_no_from' => 'nullable|string|max:50',
            'ose_numbers.*.file_no_to' => 'nullable|string|max:50',
            'documents' => 'nullable|array',
            'documents.notice_publication' => 'nullable|array',
            'documents.notice_publication.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.pleading' => 'nullable|array',
            'documents.pleading.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.protest_letter' => 'nullable|array',
            'documents.protest_letter.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.supporting' => 'nullable|array',
            'documents.supporting.*' => 'nullable|file|mimes:pdf|max:204800',
            'assigned_attorneys' => 'nullable|array',
            'assigned_attorneys.*' => 'exists:users,id',
            'assigned_clerks' => 'nullable|array',
            'assigned_clerks.*' => 'exists:users,id',
            'optional_docs' => 'nullable|array',
            'optional_docs.*.type' => 'nullable|string',
            'optional_docs.*.custom_title' => 'nullable|string|max:255',
            'optional_docs.*.files' => 'nullable|array',
            'optional_docs.*.files.*' => 'nullable|file|mimes:pdf|max:204800',
            'affirmation' => 'required|accepted',
            'action' => 'required|in:draft,validate,submit',
        ];

        if ($caseType === 'compliance') {
            $rules['pleading_type'] = 'nullable|in:request_pre_hearing,request_to_docket';
            $rules['documents.application'] = 'nullable|array';
            $rules['documents.application.*'] = 'nullable|file|mimes:pdf|max:204800';
            $rules['compliance_doc_type'] = 'required|in:compliance_order,pre_compliance_letter,compliance_letter,notice_of_violation,notice_of_reprimand';
            $rules['documents.compliance'] = 'required|array';
            $rules['documents.compliance.*'] = 'required|file|mimes:pdf|max:204800';
        } else {
            $rules['pleading_type'] = 'required|in:request_pre_hearing,request_to_docket';
            $rules['documents.application'] = 'required|array';
            $rules['documents.application.*'] = 'required|file|mimes:pdf|max:204800';
        }

        return $rules;
    }

    public function validateBusinessRules(Validator $validator): void
    {
        $data = $validator->getData();
        $caseType = $data['case_type'] ?? null;

        foreach (($data['parties'] ?? []) as $index => $party) {
            $role = $party['role'] ?? null;
            $type = $party['type'] ?? null;
            $representation = $party['representation'] ?? null;
            $attorneyOption = $party['attorney_option'] ?? null;

            if ($caseType !== 'compliance' && $role === 'respondent') {
                $validator->errors()->add("parties.{$index}.role", 'Respondent role is only allowed for compliance action cases.');
            }

            if ($type === 'individual' && (empty($party['first_name']) || empty($party['last_name']))) {
                $validator->errors()->add("parties.{$index}.first_name", 'First name and last name are required for individuals.');
            }

            if (empty($party['phone_mobile']) && empty($party['phone'])) {
                $validator->errors()->add("parties.{$index}.phone_mobile", 'A phone number is required in 555-555-5555 format.');
            }

            if ($representation === 'attorney') {
                $hasExistingAttorney = !empty($party['attorney_id']);
                $hasNewAttorney = !empty($party['attorney_name']) && !empty($party['attorney_email']);
                $hasNoAttorneyYet = $type === 'company' && $attorneyOption === 'no_attorney_yet';

                if ($type !== 'company' && $attorneyOption === 'no_attorney_yet') {
                    $validator->errors()->add("parties.{$index}.attorney_option", 'No Attorney Yet is only allowed for entities (non-person).');
                }

                if (!$hasExistingAttorney && !$hasNewAttorney && !$hasNoAttorneyYet) {
                    $validator->errors()->add("parties.{$index}.attorney_name", 'Please select an existing attorney or provide new attorney name and email.');
                }

                if ($attorneyOption === 'new' && empty($party['attorney_phone'])) {
                    $validator->errors()->add("parties.{$index}.attorney_phone", 'Attorney phone is required in 555-555-5555 format.');
                }
            }
        }
    }
}
