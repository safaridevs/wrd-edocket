<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_no')->unique();
            $table->text('caption');
            $table->string('case_type', 50);
            $table->string('status', 50)->default('draft');
            $table->string('reynolds_report_url')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users');
            $table->unsignedBigInteger('assigned_attorney_id')->nullable();
            $table->unsignedBigInteger('assigned_hydrology_expert_id')->nullable();
            $table->unsignedBigInteger('assigned_alu_clerk_id')->nullable();
            $table->unsignedBigInteger('assigned_wrd_id')->nullable();
            
            $table->index('case_no');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
