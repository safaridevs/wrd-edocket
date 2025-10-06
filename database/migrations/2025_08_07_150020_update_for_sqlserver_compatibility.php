<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only run if using SQL Server
        if (DB::getDriverName() === 'sqlsrv') {
            
            // Update cases table for SQL Server compatibility
            Schema::table('cases', function (Blueprint $table) {
                // Change JSON to NVARCHAR(MAX) for SQL Server
                $table->text('metadata_json')->nullable();
            });
            
            // Migrate existing JSON data if any
            DB::statement("UPDATE cases SET metadata_json = metadata WHERE metadata IS NOT NULL");
            
            // Drop the JSON column and rename the text column
            Schema::table('cases', function (Blueprint $table) {
                $table->dropColumn('metadata');
            });
            
            Schema::table('cases', function (Blueprint $table) {
                $table->renameColumn('metadata_json', 'metadata');
            });
            
            // Create full-text index for SQL Server (if supported)
            try {
                DB::statement("CREATE FULLTEXT INDEX ON cases(caption) KEY INDEX PK__cases__3213E83F");
            } catch (Exception $e) {
                // Ignore if full-text search is not available
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlsrv') {
            // Revert changes
            Schema::table('cases', function (Blueprint $table) {
                $table->json('metadata_json')->nullable();
            });
            
            DB::statement("UPDATE cases SET metadata_json = metadata WHERE metadata IS NOT NULL");
            
            Schema::table('cases', function (Blueprint $table) {
                $table->dropColumn('metadata');
            });
            
            Schema::table('cases', function (Blueprint $table) {
                $table->renameColumn('metadata_json', 'metadata');
            });
        }
    }
};