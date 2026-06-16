<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            if (!Schema::hasColumn('cases', 'hu_display_status')) {
                $table->string('hu_display_status')->nullable()->index();
            }

            if (!Schema::hasColumn('cases', 'hu_display_status_note')) {
                $table->text('hu_display_status_note')->nullable();
            }

            if (!Schema::hasColumn('cases', 'hu_display_status_updated_by')) {
                $table->foreignId('hu_display_status_updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('cases', 'hu_display_status_updated_at')) {
                $table->timestamp('hu_display_status_updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            if (Schema::hasColumn('cases', 'hu_display_status_updated_by')) {
                $table->dropConstrainedForeignId('hu_display_status_updated_by');
            }

            foreach (['hu_display_status', 'hu_display_status_note', 'hu_display_status_updated_at'] as $column) {
                if (Schema::hasColumn('cases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
