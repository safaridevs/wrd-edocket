<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('case_assignments') || !Schema::hasTable('users')) {
            return;
        }

        $paralegalUserIds = DB::table('users')
            ->where('role', 'alu_paralegal')
            ->when(Schema::hasTable('roles') && Schema::hasColumn('users', 'role_id'), function ($query) {
                $query->orWhereIn('role_id', DB::table('roles')->where('name', 'alu_paralegal')->select('id'));
            })
            ->pluck('id');

        DB::table('case_assignments')
            ->where('assignment_type', 'alu_clerk')
            ->whereIn('user_id', $paralegalUserIds)
            ->orderBy('id')
            ->each(function ($assignment) {
                $alreadyCorrect = DB::table('case_assignments')
                    ->where('case_id', $assignment->case_id)
                    ->where('user_id', $assignment->user_id)
                    ->where('assignment_type', 'alu_paralegal')
                    ->exists();

                if ($alreadyCorrect) {
                    DB::table('case_assignments')->where('id', $assignment->id)->delete();
                    return;
                }

                DB::table('case_assignments')->where('id', $assignment->id)->update([
                    'assignment_type' => 'alu_paralegal',
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        // This is a data correction. Reverting would incorrectly collapse valid
        // paralegal assignments back into clerk assignments.
    }
};
