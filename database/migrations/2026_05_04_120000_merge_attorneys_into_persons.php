<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attorneys') && Schema::hasTable('persons')) {
            $now = now();

            DB::table('attorneys')->orderBy('id')->get()->each(function ($attorney) use ($now) {
                if (empty($attorney->email)) {
                    return;
                }

                $person = DB::table('persons')->where('email', $attorney->email)->first();
                $name = $this->splitName($attorney->name ?? null);

                if (!$person) {
                    DB::table('persons')->insert([
                        'type' => 'individual',
                        'first_name' => $name['first_name'],
                        'last_name' => $name['last_name'],
                        'email' => $attorney->email,
                        'phone_office' => $attorney->phone ?? null,
                        'address_line1' => $attorney->address_line1 ?? null,
                        'address_line2' => $attorney->address_line2 ?? null,
                        'city' => $attorney->city ?? null,
                        'state' => $attorney->state ?? null,
                        'zip' => $attorney->zip ?? null,
                        'created_at' => $attorney->created_at ?? $now,
                        'updated_at' => $now,
                    ]);

                    return;
                }

                DB::table('persons')
                    ->where('id', $person->id)
                    ->update([
                        'first_name' => $person->first_name ?: $name['first_name'],
                        'last_name' => $person->last_name ?: $name['last_name'],
                        'phone_office' => $person->phone_office ?: ($attorney->phone ?? null),
                        'address_line1' => $person->address_line1 ?: ($attorney->address_line1 ?? null),
                        'address_line2' => $person->address_line2 ?: ($attorney->address_line2 ?? null),
                        'city' => $person->city ?: ($attorney->city ?? null),
                        'state' => $person->state ?: ($attorney->state ?? null),
                        'zip' => $person->zip ?: ($attorney->zip ?? null),
                        'updated_at' => $now,
                    ]);
            });
        }

        if (Schema::hasTable('attorney_client_relationships') && Schema::hasTable('attorneys') && Schema::hasTable('case_parties')) {
            $now = now();

            DB::table('attorney_client_relationships')
                ->where('status', 'active')
                ->orderBy('id')
                ->get()
                ->each(function ($relationship) use ($now) {
                    $attorney = DB::table('attorneys')->where('id', $relationship->attorney_id)->first();
                    if (!$attorney || empty($attorney->email)) {
                        return;
                    }

                    $attorneyPerson = DB::table('persons')->where('email', $attorney->email)->first();
                    $clientParty = DB::table('case_parties')
                        ->where('case_id', $relationship->case_id)
                        ->where('person_id', $relationship->client_person_id)
                        ->whereNotIn('role', ['counsel', 'paralegal', 'agent'])
                        ->first();

                    if (!$attorneyPerson || !$clientParty) {
                        return;
                    }

                    $exists = DB::table('case_parties')
                        ->where('case_id', $relationship->case_id)
                        ->where('person_id', $attorneyPerson->id)
                        ->where('role', 'counsel')
                        ->where('client_party_id', $clientParty->id)
                        ->exists();

                    if (!$exists) {
                        DB::table('case_parties')->insert([
                            'case_id' => $relationship->case_id,
                            'person_id' => $attorneyPerson->id,
                            'role' => 'counsel',
                            'client_party_id' => $clientParty->id,
                            'service_enabled' => true,
                            'created_at' => $relationship->created_at ?? $now,
                            'updated_at' => $now,
                        ]);
                    }
                });
        }

        if (Schema::hasTable('attorney_client_relationships')) {
            Schema::drop('attorney_client_relationships');
        }

        if (Schema::hasTable('attorneys')) {
            Schema::drop('attorneys');
        }

        if (Schema::hasTable('users')) {
            $userAttorneyColumns = array_values(array_filter(
                ['is_attorney', 'bar_number', 'law_firm'],
                fn (string $column) => Schema::hasColumn('users', $column)
            ));

            if (!empty($userAttorneyColumns)) {
                Schema::table('users', function (Blueprint $table) use ($userAttorneyColumns) {
                    $table->dropColumn($userAttorneyColumns);
                });
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('attorneys')) {
            Schema::create('attorneys', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('bar_number')->nullable();
                $table->string('address_line1')->nullable();
                $table->string('address_line2')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('zip')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('attorney_client_relationships')) {
            Schema::create('attorney_client_relationships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attorney_id')->constrained('attorneys')->onDelete('cascade');
                $table->foreignId('client_person_id')->constrained('persons')->onDelete('cascade');
                $table->foreignId('case_id')->constrained('cases')->onDelete('cascade');
                $table->string('status')->default('active');
                $table->date('effective_date');
                $table->date('termination_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'is_attorney')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_attorney')->default(false);
                $table->string('bar_number')->nullable();
                $table->string('law_firm')->nullable();
            });
        }
    }

    private function splitName(?string $name): array
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];

        return [
            'first_name' => $parts[0] ?? null,
            'last_name' => count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null,
        ];
    }
};
