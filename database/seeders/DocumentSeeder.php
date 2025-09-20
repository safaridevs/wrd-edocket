<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\CaseModel;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $aluClerk = User::where('role', 'alu_clerk')->first();
        $huAdmin = User::where('role', 'hu_admin')->first();
        $cases = CaseModel::all();

        foreach ($cases as $case) {
            // Application document
            Document::create([
                'case_id' => $case->id,
                'doc_type' => 'application',
                'original_filename' => "2025-01-15 — Application — Water Rights — {$case->case_no}.pdf",
                'stored_filename' => "app_{$case->id}_1.pdf",
                'mime' => 'application/pdf',
                'size_bytes' => 1024000,
                'checksum' => md5("application_{$case->id}"),
                'storage_uri' => "cases/{$case->id}/documents/app_{$case->id}_1.pdf",
                'uploaded_by_user_id' => $aluClerk->id,
                'uploaded_at' => now()->subDays(5),
                'stamped' => $case->status === 'active',
                'stamp_text' => $case->status === 'active' ? "ELECTRONICALLY FILED\n" . now()->format('D m/d/Y @ g:i A') . "\nOSE HEARING UNIT / {$huAdmin->initials}" : null,
                'approved' => $case->status === 'active',
                'approved_by_user_id' => $case->status === 'active' ? $huAdmin->id : null,
                'approved_at' => $case->status === 'active' ? now()->subDays(3) : null,
            ]);

            // Notice of Publication
            Document::create([
                'case_id' => $case->id,
                'doc_type' => 'notice_publication',
                'original_filename' => "2025-01-15 — Notice of Publication — Water Rights — {$case->case_no}.docx",
                'stored_filename' => "notice_{$case->id}_1.docx",
                'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'size_bytes' => 512000,
                'checksum' => md5("notice_{$case->id}"),
                'storage_uri' => "cases/{$case->id}/documents/notice_{$case->id}_1.docx",
                'uploaded_by_user_id' => $aluClerk->id,
                'uploaded_at' => now()->subDays(5),
                'approved' => true,
                'approved_by_user_id' => $huAdmin->id,
                'approved_at' => now()->subDays(3),
            ]);

            // Request to Docket
            Document::create([
                'case_id' => $case->id,
                'doc_type' => 'request_to_docket',
                'original_filename' => "2025-01-15 — Request to Docket — Hearing Schedule — {$case->case_no}.pdf",
                'stored_filename' => "request_{$case->id}_1.pdf",
                'mime' => 'application/pdf',
                'size_bytes' => 256000,
                'checksum' => md5("request_{$case->id}"),
                'storage_uri' => "cases/{$case->id}/documents/request_{$case->id}_1.pdf",
                'uploaded_by_user_id' => $aluClerk->id,
                'uploaded_at' => now()->subDays(5),
                'stamped' => $case->status === 'active',
                'stamp_text' => $case->status === 'active' ? "ELECTRONICALLY FILED\n" . now()->format('D m/d/Y @ g:i A') . "\nOSE HEARING UNIT / {$huAdmin->initials}" : null,
                'approved' => $case->status === 'active',
                'approved_by_user_id' => $case->status === 'active' ? $huAdmin->id : null,
                'approved_at' => $case->status === 'active' ? now()->subDays(3) : null,
            ]);
        }
    }
}