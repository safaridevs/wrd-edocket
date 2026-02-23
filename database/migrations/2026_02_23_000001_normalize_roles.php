<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $canonical = [
            'admin' => ['display_name' => 'Admin', 'group' => 'admin'],
            'hu_admin' => ['display_name' => 'HU Admin', 'group' => 'hu'],
            'hu_clerk' => ['display_name' => 'HU Clerk', 'group' => 'hu'],
            'alu_mgr' => ['display_name' => 'ALU Manager', 'group' => 'alu'],
            'alu_clerk' => ['display_name' => 'ALU Clerk', 'group' => 'alu'],
            'alu_atty' => ['display_name' => 'ALU Attorney', 'group' => 'alu'],
            'wrd' => ['display_name' => 'WRD', 'group' => 'wrd'],
            'wrap_dir' => ['display_name' => 'WRAP Director', 'group' => 'wrd'],
            'hydrology_expert' => ['display_name' => 'Hydrology Expert', 'group' => 'wrd'],
            'party' => ['display_name' => 'Party', 'group' => 'party'],
            'unaffiliated' => ['display_name' => 'Unaffiliated', 'group' => 'party'],
        ];

        $aliases = [
            'alu_attorney' => 'alu_atty',
            'alu_managing_atty' => 'alu_mgr',
            'alu_manager' => 'alu_mgr',
            'wrap_director' => 'wrap_dir',
            'wrd_expert' => 'wrd',
            'hu_law_clerk' => 'hu_clerk',
            'hu_examiner' => 'hu_clerk',
            'system_admin' => 'admin',
        ];

        foreach ($aliases as $alias => $canonicalName) {
            DB::table('users')->where('role', $alias)->update(['role' => $canonicalName]);

            $aliasRole = DB::table('roles')->where('name', $alias)->first();
            if (!$aliasRole) {
                continue;
            }

            $canonicalRole = DB::table('roles')->where('name', $canonicalName)->first();
            if ($canonicalRole) {
                // Avoid unique collisions by removing duplicates before update
                $docTypeIds = DB::table('document_type_role')
                    ->where('role_id', $aliasRole->id)
                    ->pluck('document_type_id')
                    ->all();
                if (!empty($docTypeIds)) {
                    DB::table('document_type_role')
                        ->where('role_id', $canonicalRole->id)
                        ->whereIn('document_type_id', $docTypeIds)
                        ->delete();
                    DB::table('document_type_role')
                        ->where('role_id', $aliasRole->id)
                        ->update(['role_id' => $canonicalRole->id]);
                }
                DB::table('roles')->where('id', $aliasRole->id)->delete();
            } else {
                $meta = $canonical[$canonicalName] ?? ['display_name' => $canonicalName, 'group' => null];
                DB::table('roles')->where('id', $aliasRole->id)->update([
                    'name' => $canonicalName,
                    'display_name' => $meta['display_name'],
                    'group' => $meta['group'],
                    'is_active' => true,
                ]);
            }
        }

        foreach ($canonical as $name => $meta) {
            $role = DB::table('roles')->where('name', $name)->first();
            if (!$role) {
                DB::table('roles')->insert([
                    'name' => $name,
                    'display_name' => $meta['display_name'],
                    'group' => $meta['group'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('roles')->where('id', $role->id)->update([
                    'display_name' => $meta['display_name'],
                    'group' => $meta['group'],
                    'is_active' => true,
                ]);
            }
        }

        DB::table('roles')->whereNotIn('name', array_keys($canonical))->update(['is_active' => false]);

        DB::table('case_assignments')
            ->where('assignment_type', 'alu_attorney')
            ->update(['assignment_type' => 'alu_atty']);
    }

    public function down(): void
    {
        // No-op: this normalization is not easily reversible.
    }
};
