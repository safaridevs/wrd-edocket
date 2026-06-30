<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attorneys')) {
            return;
        }

        Schema::table('attorneys', function (Blueprint $table) {
            if (!Schema::hasColumn('attorneys', 'address_line1')) {
                $table->string('address_line1')->nullable()->after('bar_number');
            }
            if (!Schema::hasColumn('attorneys', 'address_line2')) {
                $table->string('address_line2')->nullable()->after('address_line1');
            }
            if (!Schema::hasColumn('attorneys', 'city')) {
                $table->string('city')->nullable()->after('address_line2');
            }
            if (!Schema::hasColumn('attorneys', 'state')) {
                $table->string('state', 2)->nullable()->after('city');
            }
            if (!Schema::hasColumn('attorneys', 'zip')) {
                $table->string('zip', 10)->nullable()->after('state');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('attorneys')) {
            return;
        }

        $columns = array_values(array_filter(
            ['address_line1', 'address_line2', 'city', 'state', 'zip'],
            fn (string $column) => Schema::hasColumn('attorneys', $column)
        ));

        if (empty($columns)) {
            return;
        }

        Schema::table('attorneys', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
};
