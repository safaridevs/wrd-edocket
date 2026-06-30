<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('notification_type', 50);
            $table->json('payload_json')->nullable();
            $table->timestamp('sent_at');
            
            $table->index(['case_id', 'notification_type']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
