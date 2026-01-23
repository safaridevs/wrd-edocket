<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('email_status')->default('pending')->after('sent_at'); // pending, sent, delivered, bounced, failed
            $table->text('bounce_reason')->nullable()->after('email_status');
            $table->timestamp('delivered_at')->nullable()->after('bounce_reason');
            $table->timestamp('bounced_at')->nullable()->after('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['email_status', 'bounce_reason', 'delivered_at', 'bounced_at']);
        });
    }
};
