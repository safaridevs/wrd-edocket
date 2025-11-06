<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove any existing notification_type constraints to allow flexible types
        $constraints = DB::select("SELECT name FROM sys.check_constraints WHERE parent_object_id = OBJECT_ID('notifications') AND definition LIKE '%notification_type%'");
        
        foreach ($constraints as $constraint) {
            DB::statement("ALTER TABLE notifications DROP CONSTRAINT {$constraint->name}");
        }
        
        // No need to add constraints - notification_type field should accept any string
    }

    public function down(): void
    {
        // No rollback needed
    }
};