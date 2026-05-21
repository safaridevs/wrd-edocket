<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('case_parties') && !Schema::hasColumn('case_parties', 'representation_capacity')) {
            Schema::table('case_parties', function (Blueprint $table) {
                $table->string('representation_capacity', 50)->nullable();
            });
        }

        if (Schema::hasTable('roles')) {
            $externalRoleId = DB::table('roles')->where('name', 'external_attorney')->value('id');

            if (!$externalRoleId) {
                $externalRoleId = DB::table('roles')->insertGetId([
                    'name' => 'external_attorney',
                    'display_name' => 'External Attorney',
                    'group' => 'party',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('roles')->where('id', $externalRoleId)->update([
                    'display_name' => 'External Attorney',
                    'group' => 'party',
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
            }

            $partyRoleId = DB::table('roles')->where('name', 'party')->value('id');

            if ($partyRoleId && Schema::hasTable('document_type_role')) {
                $documentTypeIds = DB::table('document_type_role')
                    ->where('role_id', $partyRoleId)
                    ->pluck('document_type_id');

                foreach ($documentTypeIds as $documentTypeId) {
                    $exists = DB::table('document_type_role')
                        ->where('role_id', $externalRoleId)
                        ->where('document_type_id', $documentTypeId)
                        ->exists();

                    if (!$exists) {
                        DB::table('document_type_role')->insert([
                            'role_id' => $externalRoleId,
                            'document_type_id' => $documentTypeId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        if (Schema::hasTable('case_parties') && Schema::hasColumn('case_parties', 'representation_capacity')) {
            DB::table('case_parties')
                ->where('role', 'counsel')
                ->whereNull('representation_capacity')
                ->update(['representation_capacity' => 'private_counsel']);
        }

        if (Schema::hasTable('users') && Schema::hasTable('persons') && Schema::hasTable('case_parties')) {
            $externalRoleId = Schema::hasTable('roles')
                ? DB::table('roles')->where('name', 'external_attorney')->value('id')
                : null;

            $counselEmails = DB::table('case_parties')
                ->join('persons', 'case_parties.person_id', '=', 'persons.id')
                ->where('case_parties.role', 'counsel')
                ->whereNotNull('persons.email')
                ->pluck('persons.email')
                ->map(fn ($email) => strtolower(trim((string) $email)))
                ->filter()
                ->unique();

            foreach ($counselEmails as $email) {
                DB::table('users')
                    ->where('role', 'party')
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->update(array_filter([
                        'role' => 'external_attorney',
                        'role_id' => $externalRoleId,
                    ], fn ($value) => !is_null($value)));
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('case_parties') && Schema::hasColumn('case_parties', 'representation_capacity')) {
            Schema::table('case_parties', function (Blueprint $table) {
                $table->dropColumn('representation_capacity');
            });
        }
    }
};
