<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $wrdParties = DB::table('case_parties')
            ->join('persons', 'case_parties.person_id', '=', 'persons.id')
            ->select(
                'case_parties.id as case_party_id',
                'case_parties.case_id',
                'case_parties.person_id',
                'persons.city',
                'persons.address_line1'
            )
            ->whereRaw("UPPER(LTRIM(RTRIM(COALESCE(persons.organization, '')))) = 'WATER RIGHTS DIVISION'")
            ->orderBy('case_parties.case_id')
            ->get()
            ->groupBy('case_id');

        foreach ($wrdParties as $caseId => $partyGroup) {
            $case = DB::table('cases')
                ->select('id', 'metadata')
                ->where('id', $caseId)
                ->first();

            if (!$case) {
                continue;
            }

            $metadata = [];
            if (is_string($case->metadata) && $case->metadata !== '') {
                $metadata = json_decode($case->metadata, true) ?? [];
            }

            if (empty($metadata['wrd_office'])) {
                $metadata['wrd_office'] = $this->inferWrdOffice($partyGroup->first());

                DB::table('cases')
                    ->where('id', $caseId)
                    ->update([
                        'metadata' => json_encode($metadata),
                    ]);
            }

            $personIds = $partyGroup->pluck('person_id')->unique()->values();
            $casePartyIds = $partyGroup->pluck('case_party_id')->unique()->values();

            DB::table('service_list')
                ->where('case_id', $caseId)
                ->whereIn('person_id', $personIds)
                ->delete();

            DB::table('case_parties')
                ->whereIn('id', $casePartyIds)
                ->delete();
        }
    }

    public function down(): void
    {
        // Legacy WRD pseudo-party rows are intentionally not recreated.
    }

    private function inferWrdOffice(object $party): string
    {
        $city = strtolower(trim((string) ($party->city ?? '')));
        $address = strtolower(trim((string) ($party->address_line1 ?? '')));

        if (str_contains($city, 'albuquerque') || str_contains($address, 'san antonio')) {
            return 'albuquerque';
        }

        return 'santa_fe';
    }
};
