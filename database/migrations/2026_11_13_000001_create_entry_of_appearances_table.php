<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entry_of_appearances', function (Blueprint $table) {
            $table->id();
            // Reference values only: the intake/audit record must not depend on
            // the lifecycle of the case or its parties.
            $table->unsignedBigInteger('case_id');
            $table->unsignedBigInteger('represented_case_party_id');
            $table->string('status', 30)->default('unverified');
            $table->string('submitted_case_number');
            $table->string('submitted_party_name');
            $table->string('attorney_prefix')->nullable();
            $table->string('attorney_first_name');
            $table->string('attorney_middle_name')->nullable();
            $table->string('attorney_last_name');
            $table->string('attorney_suffix')->nullable();
            $table->string('law_firm');
            $table->string('email');
            $table->string('phone', 50);
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city');
            $table->string('state', 50);
            $table->string('zip', 20);
            $table->date('effective_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('verification_expires_at')->nullable();
            $table->string('submission_ip', 45)->nullable();
            $table->text('submission_user_agent')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('person_id')->nullable()->constrained('persons')->nullOnDelete();
            $table->unsignedBigInteger('counsel_case_party_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('case_id');
            $table->index('represented_case_party_id');
            $table->index('counsel_case_party_id');
            $table->index(['case_id', 'represented_case_party_id', 'status'], 'entry_appearance_case_party_status');
            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entry_of_appearances');
    }
};
