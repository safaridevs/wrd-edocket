<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('roles');
        });

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

        foreach ($aliases as $alias => $canonical) {
            DB::table('users')->where('role', $alias)->update(['role' => $canonical]);
        }

        $roles = DB::table('roles')->pluck('id', 'name');

        foreach ($roles as $name => $id) {
            DB::table('users')
                ->where('role', $name)
                ->update(['role_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
    }
};
