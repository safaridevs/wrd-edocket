<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('group')->nullable(); // alu, hu, party, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed initial roles
        DB::table('roles')->insert([
            ['name' => 'admin', 'display_name' => 'Admin', 'group' => 'admin', 'is_active' => true],
            ['name' => 'hu_admin', 'display_name' => 'HU Admin', 'group' => 'hu', 'is_active' => true],
            ['name' => 'hu_clerk', 'display_name' => 'HU Clerk', 'group' => 'hu', 'is_active' => true],
            ['name' => 'alu_mgr', 'display_name' => 'ALU Manager', 'group' => 'alu', 'is_active' => true],
            ['name' => 'alu_clerk', 'display_name' => 'ALU Clerk', 'group' => 'alu', 'is_active' => true],
            ['name' => 'alu_atty', 'display_name' => 'ALU Attorney', 'group' => 'alu', 'is_active' => true],
            ['name' => 'wrd', 'display_name' => 'WRD', 'group' => 'wrd', 'is_active' => true],
            ['name' => 'wrap_dir', 'display_name' => 'WRAP Director', 'group' => 'wrd', 'is_active' => true],
            ['name' => 'hydrology_expert', 'display_name' => 'Hydrology Expert', 'group' => 'wrd', 'is_active' => true],
            ['name' => 'party', 'display_name' => 'Party', 'group' => 'party', 'is_active' => true],
            ['name' => 'unaffiliated', 'display_name' => 'Unaffiliated', 'group' => 'party', 'is_active' => true],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
