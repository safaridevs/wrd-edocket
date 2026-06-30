<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('document_types') || !Schema::hasColumn('document_types', 'allowed_roles')) {
            return;
        }

        if (Schema::hasTable('document_type_role') && Schema::hasTable('roles')) {
            DB::table('document_types')
                ->whereNotNull('allowed_roles')
                ->orderBy('id')
                ->get(['id', 'allowed_roles'])
                ->each(function ($documentType) {
                    $roleNames = json_decode((string) $documentType->allowed_roles, true);

                    if (!is_array($roleNames)) {
                        return;
                    }

                    foreach ($roleNames as $roleName) {
                        $roleId = DB::table('roles')->where('name', $roleName)->value('id');

                        if (!$roleId) {
                            continue;
                        }

                        $exists = DB::table('document_type_role')
                            ->where('document_type_id', $documentType->id)
                            ->where('role_id', $roleId)
                            ->exists();

                        if (!$exists) {
                            DB::table('document_type_role')->insert([
                                'document_type_id' => $documentType->id,
                                'role_id' => $roleId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                });
        }

        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('allowed_roles');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('document_types') || Schema::hasColumn('document_types', 'allowed_roles')) {
            return;
        }

        Schema::table('document_types', function (Blueprint $table) {
            $table->json('allowed_roles')->nullable()->after('category');
        });
    }
};
