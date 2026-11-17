<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $contractRoleId = DB::table('roles')->where('name', 'contract_attorney')->value('id');

        $sourceAssignments = DB::table('case_assignments as source_assignment')
            ->join('case_consolidation_sources as source', 'source.source_case_id', '=', 'source_assignment.case_id')
            ->join('case_consolidations as consolidation', 'consolidation.id', '=', 'source.case_consolidation_id')
            ->join('users as assigning_attorney', 'assigning_attorney.id', '=', 'source_assignment.assigned_by')
            ->whereIn('source_assignment.assignment_type', ['alu_paralegal', 'contract_paralegal'])
            ->where(function ($query) use ($contractRoleId) {
                $query->where('assigning_attorney.role', 'contract_attorney');
                if ($contractRoleId) {
                    $query->orWhere('assigning_attorney.role_id', $contractRoleId);
                }
            })
            ->select([
                'consolidation.combined_case_id',
                'source_assignment.user_id',
                'source_assignment.assigned_by',
            ])
            ->distinct()
            ->get();

        foreach ($sourceAssignments as $sourceAssignment) {
            $combinedAssignment = DB::table('case_assignments')
                ->where('case_id', $sourceAssignment->combined_case_id)
                ->where('user_id', $sourceAssignment->user_id)
                ->whereIn('assignment_type', ['alu_paralegal', 'contract_paralegal'])
                ->orderByRaw("CASE WHEN assignment_type = 'contract_paralegal' THEN 0 ELSE 1 END")
                ->first();

            if (!$combinedAssignment) {
                DB::table('case_assignments')->insert([
                    'case_id' => $sourceAssignment->combined_case_id,
                    'user_id' => $sourceAssignment->user_id,
                    'assignment_type' => 'contract_paralegal',
                    'assigned_by' => $sourceAssignment->assigned_by,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('case_assignments')
                ->where('id', $combinedAssignment->id)
                ->update([
                    'assignment_type' => 'contract_paralegal',
                    'assigned_by' => $sourceAssignment->assigned_by,
                    'updated_at' => now(),
                ]);

            DB::table('case_assignments')
                ->where('case_id', $sourceAssignment->combined_case_id)
                ->where('user_id', $sourceAssignment->user_id)
                ->whereIn('assignment_type', ['alu_paralegal', 'contract_paralegal'])
                ->where('id', '!=', $combinedAssignment->id)
                ->delete();
        }
    }

    public function down(): void
    {
        // Ownership restoration is intentionally retained on rollback because
        // the original assigning attorney is the authoritative relationship.
    }
};
