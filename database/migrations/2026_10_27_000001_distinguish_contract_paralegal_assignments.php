<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $contractAttorneyRoleId = DB::table('roles')->where('name', 'contract_attorney')->value('id');
        $aluParalegalRoleId = DB::table('roles')->where('name', 'alu_paralegal')->value('id');

        $contractAttorneyIds = DB::table('users')
            ->where(function ($query) use ($contractAttorneyRoleId) {
                $query->where('role', 'contract_attorney');
                if ($contractAttorneyRoleId) {
                    $query->orWhere('role_id', $contractAttorneyRoleId);
                }
            })
            ->pluck('id');

        if ($contractAttorneyIds->isEmpty()) {
            return;
        }

        $aluParalegalIds = DB::table('users')
            ->where(function ($query) use ($aluParalegalRoleId) {
                $query->where('role', 'alu_paralegal');
                if ($aluParalegalRoleId) {
                    $query->orWhere('role_id', $aluParalegalRoleId);
                }
            })
            ->pluck('id');

        $assignments = DB::table('case_assignments')
            ->where('assignment_type', 'alu_paralegal')
            ->whereIn('assigned_by', $contractAttorneyIds)
            ->when($aluParalegalIds->isNotEmpty(), fn ($query) => $query->whereNotIn('user_id', $aluParalegalIds))
            ->get(['id', 'case_id', 'user_id']);

        foreach ($assignments as $assignment) {
            $alreadyExists = DB::table('case_assignments')
                ->where('case_id', $assignment->case_id)
                ->where('user_id', $assignment->user_id)
                ->where('assignment_type', 'contract_paralegal')
                ->exists();

            if ($alreadyExists) {
                DB::table('case_assignments')->where('id', $assignment->id)->delete();
            } else {
                DB::table('case_assignments')->where('id', $assignment->id)
                    ->update(['assignment_type' => 'contract_paralegal']);
            }
        }
    }

    public function down(): void
    {
        $assignments = DB::table('case_assignments')
            ->where('assignment_type', 'contract_paralegal')
            ->get(['id', 'case_id', 'user_id']);

        foreach ($assignments as $assignment) {
            $alreadyExists = DB::table('case_assignments')
                ->where('case_id', $assignment->case_id)
                ->where('user_id', $assignment->user_id)
                ->where('assignment_type', 'alu_paralegal')
                ->exists();

            if ($alreadyExists) {
                DB::table('case_assignments')->where('id', $assignment->id)->delete();
            } else {
                DB::table('case_assignments')->where('id', $assignment->id)
                    ->update(['assignment_type' => 'alu_paralegal']);
            }
        }
    }
};
