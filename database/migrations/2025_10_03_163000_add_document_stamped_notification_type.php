<?php

use App\Support\SchemaCompat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the index first
        SchemaCompat::dropIndexIfExists('notifications', 'notifications_case_id_notification_type_index');

        SchemaCompat::dropCheckConstraints('notifications', 'notification_type');

        // Drop and recreate the column with new values
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('notification_type');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->string('notification_type', 50)->default('case_initiated')->after('case_id');
        });

        SchemaCompat::createIndexIfMissing(
            'notifications',
            'notifications_case_id_notification_type_index',
            ['case_id', 'notification_type']
        );
    }

    public function down(): void
    {
        SchemaCompat::dropIndexIfExists('notifications', 'notifications_case_id_notification_type_index');

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('notification_type');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->string('notification_type', 50)->default('case_initiated')->after('case_id');
        });

        SchemaCompat::createIndexIfMissing(
            'notifications',
            'notifications_case_id_notification_type_index',
            ['case_id', 'notification_type']
        );
    }
};
