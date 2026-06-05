<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('roles')->updateOrInsert(
            ['name' => 'alu_paralegal'],
            [
                'display_name' => 'ALU Paralegal',
                'group' => 'alu',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $aluParalegalRoleId = DB::table('roles')->where('name', 'alu_paralegal')->value('id');
        $aluClerkRoleId = DB::table('roles')->where('name', 'alu_clerk')->value('id');

        if ($aluParalegalRoleId && $aluClerkRoleId) {
            DB::table('users')
                ->where('role', 'alu_paralegal')
                ->update(['role_id' => $aluParalegalRoleId]);

            $existingDocumentTypeIds = DB::table('document_type_role')
                ->where('role_id', $aluParalegalRoleId)
                ->pluck('document_type_id')
                ->all();

            $documentTypeIds = DB::table('document_type_role')
                ->where('role_id', $aluClerkRoleId)
                ->whereNotIn('document_type_id', $existingDocumentTypeIds)
                ->pluck('document_type_id');

            $rows = $documentTypeIds->map(fn ($documentTypeId) => [
                'document_type_id' => $documentTypeId,
                'role_id' => $aluParalegalRoleId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            if (!empty($rows)) {
                DB::table('document_type_role')->insert($rows);
            }
        }
    }

    public function down(): void
    {
        $aluParalegalRoleId = DB::table('roles')->where('name', 'alu_paralegal')->value('id');

        if ($aluParalegalRoleId) {
            DB::table('document_type_role')->where('role_id', $aluParalegalRoleId)->delete();
            DB::table('roles')->where('id', $aluParalegalRoleId)->update(['is_active' => false]);
        }
    }
};
