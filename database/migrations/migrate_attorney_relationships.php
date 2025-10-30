<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\CaseParty;
use App\Models\AttorneyClientRelationship;

return new class extends Migration
{
    public function up()
    {
        // Migrate existing CaseParty.attorney_id relationships to AttorneyClientRelationship
        $partiesWithAttorneys = CaseParty::whereNotNull('attorney_id')->get();
        
        foreach ($partiesWithAttorneys as $party) {
            // Check if relationship already exists
            $exists = AttorneyClientRelationship::where('attorney_id', $party->attorney_id)
                ->where('client_person_id', $party->person_id)
                ->where('case_id', $party->case_id)
                ->exists();
                
            if (!$exists) {
                AttorneyClientRelationship::create([
                    'attorney_id' => $party->attorney_id,
                    'client_person_id' => $party->person_id,
                    'case_id' => $party->case_id,
                    'status' => 'active',
                    'effective_date' => $party->created_at ?? now(),
                    'notes' => 'Migrated from case party assignment'
                ]);
            }
        }
    }
    
    public function down()
    {
        // Remove migrated relationships
        AttorneyClientRelationship::where('notes', 'Migrated from case party assignment')->delete();
    }
};