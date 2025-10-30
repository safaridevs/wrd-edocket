<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE cases ADD assigned_alu_clerk_id BIGINT NULL');
        DB::statement('ALTER TABLE cases ADD assigned_wrd_id BIGINT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cases DROP COLUMN assigned_alu_clerk_id');
        DB::statement('ALTER TABLE cases DROP COLUMN assigned_wrd_id');
    }
};