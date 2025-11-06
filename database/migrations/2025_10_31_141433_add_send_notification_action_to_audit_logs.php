<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration ensures the send_notification action is supported
        // The audit_logs table already supports any action string via the action column
    }

    public function down(): void
    {
        // No changes needed
    }
};