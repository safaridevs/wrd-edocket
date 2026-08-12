<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $contractRoleId = DB::table('roles')->where('name', 'contract_attorney')->value('id');

        if (!$contractRoleId) {
            $contractRoleId = DB::table('roles')->insertGetId([
                'name' => 'contract_attorney',
                'display_name' => 'Contract Attorney',
                'group' => 'alu',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('roles')->where('id', $contractRoleId)->update([
                'display_name' => 'Contract Attorney',
                'group' => 'alu',
                'is_active' => true,
                'updated_at' => now(),
            ]);
        }

        $aluAttorneyRoleId = DB::table('roles')->where('name', 'alu_atty')->value('id');

        if ($aluAttorneyRoleId && Schema::hasTable('document_type_role')) {
            $documentTypeIds = DB::table('document_type_role')
                ->where('role_id', $aluAttorneyRoleId)
                ->pluck('document_type_id');

            foreach ($documentTypeIds as $documentTypeId) {
                DB::table('document_type_role')->updateOrInsert(
                    ['document_type_id' => $documentTypeId, 'role_id' => $contractRoleId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        if (!Schema::hasTable('users') || !Schema::hasTable('case_assignments')) {
            return;
        }

        $assignedContractUserIds = DB::table('case_assignments')
            ->whereIn('assignment_type', ['alu_atty', 'alu_attorney'])
            ->pluck('user_id')
            ->unique();

        $externalRoleId = DB::table('roles')->where('name', 'external_attorney')->value('id');

        DB::table('users')
            ->whereIn('id', $assignedContractUserIds)
            ->where(function ($query) use ($externalRoleId) {
                $query->where('role', 'external_attorney');
                if ($externalRoleId) {
                    $query->orWhere('role_id', $externalRoleId);
                }
            })
            ->update([
                'role' => 'contract_attorney',
                'role_id' => $contractRoleId,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $contractRoleId = DB::table('roles')->where('name', 'contract_attorney')->value('id');
        $externalRoleId = DB::table('roles')->where('name', 'external_attorney')->value('id');

        if ($contractRoleId && Schema::hasTable('users')) {
            DB::table('users')->where('role_id', $contractRoleId)->update([
                'role' => 'external_attorney',
                'role_id' => $externalRoleId,
                'updated_at' => now(),
            ]);
        }

        if ($contractRoleId && Schema::hasTable('document_type_role')) {
            DB::table('document_type_role')->where('role_id', $contractRoleId)->delete();
        }

        if ($contractRoleId) {
            DB::table('roles')->where('id', $contractRoleId)->delete();
        }
    }
};
