<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('attorney_client_relationships') || !Schema::hasColumn('case_parties', 'attorney_id')) {
            return;
        }

        // Legacy migration: move old CaseParty.attorney_id links into the old relationship table.
        $partiesWithAttorneys = DB::table('case_parties')->whereNotNull('attorney_id')->get();
        
        foreach ($partiesWithAttorneys as $party) {
            // Check if relationship already exists
            $exists = DB::table('attorney_client_relationships')
                ->where('attorney_id', $party->attorney_id)
                ->where('client_person_id', $party->person_id)
                ->where('case_id', $party->case_id)
                ->exists();
                
            if (!$exists) {
                DB::table('attorney_client_relationships')->insert([
                    'attorney_id' => $party->attorney_id,
                    'client_person_id' => $party->person_id,
                    'case_id' => $party->case_id,
                    'status' => 'active',
                    'effective_date' => $party->created_at ?? now(),
                    'notes' => 'Migrated from case party assignment',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
    
    public function down()
    {
        if (!Schema::hasTable('attorney_client_relationships')) {
            return;
        }

        // Remove migrated relationships
        DB::table('attorney_client_relationships')
            ->where('notes', 'Migrated from case party assignment')
            ->delete();
    }
};
