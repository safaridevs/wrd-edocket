<?php

use App\Models\Document;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('filing_context', 40)->nullable();
            $table->index('filing_context');
        });

        DB::table('documents')
            ->leftJoin('cases', 'cases.id', '=', 'documents.case_id')
            ->leftJoin('users', 'users.id', '=', 'documents.uploaded_by_user_id')
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->select([
                'documents.id',
                'documents.uploaded_at',
                'cases.accepted_at',
                'users.role',
                'roles.name as related_role',
            ])
            ->orderBy('documents.id')
            ->chunk(500, function ($documents) {
                foreach ($documents as $document) {
                    $role = strtolower(trim((string) ($document->related_role ?: $document->role)));
                    $isHearingUnit = in_array($role, ['hu_admin', 'hu_clerk', 'hu_law_clerk', 'hu_examiner'], true);
                    $wasUploadedAfterAcceptance = $document->accepted_at
                        && $document->uploaded_at
                        && Carbon::parse($document->uploaded_at)->gt(Carbon::parse($document->accepted_at));

                    $context = match (true) {
                        $isHearingUnit => Document::FILING_CONTEXT_HEARING_UNIT,
                        $wasUploadedAfterAcceptance => Document::FILING_CONTEXT_SUBSEQUENT,
                        default => Document::FILING_CONTEXT_INITIAL,
                    };

                    DB::table('documents')
                        ->where('id', $document->id)
                        ->update(['filing_context' => $context]);
                }
            });

    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['filing_context']);
            $table->dropColumn('filing_context');
        });
    }
};
