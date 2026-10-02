<?php

namespace Tests\Feature;

use App\Models\CaseModel;
use App\Models\CaseParty;
use App\Models\Document;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Document downloads must be authorized (backlog SEC-19) and files must never be web-served
 * from a public folder (SEC-18).
 */
class DocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');
        Storage::fake('public');
        Storage::fake('private');

        $this->creator = $this->user('alu_clerk');
    }

    public function test_user_not_on_the_case_cannot_fetch_a_pending_document(): void
    {
        $document = $this->document($this->case('active'), approved: false, stamped: false);

        foreach (['party', 'interested_party', 'external_attorney'] as $role) {
            $this->actingAs($this->user($role))->get(route('documents.download', $document))->assertForbidden();
            $this->actingAs($this->user($role))->get(route('documents.preview', $document))->assertForbidden();
        }
    }

    public function test_party_on_the_case_can_fetch_its_pending_document(): void
    {
        $case = $this->case('active');
        $party = $this->user('party');
        $this->addParty($case, $party->email);
        $document = $this->document($case, approved: false, stamped: false, contents: 'pending filing');

        $response = $this->actingAs($party)->get(route('documents.download', $document));

        $response->assertOk();
        $this->assertSame('pending filing', $response->streamedContent());
        $this->assertStringStartsWith('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_preview_is_served_inline(): void
    {
        $document = $this->document($this->case('active'), approved: false, stamped: false);

        $response = $this->actingAs($this->user('hu_clerk'))->get(route('documents.preview', $document));

        $response->assertOk();
        $this->assertStringStartsWith('inline', $response->headers->get('Content-Disposition'));
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_publicly_available_document_is_open_to_any_signed_in_user(): void
    {
        $document = $this->document($this->case('active'), approved: true, stamped: true);

        $this->actingAs($this->user('party'))->get(route('documents.download', $document))->assertOk();
    }

    public function test_hearing_unit_cannot_fetch_documents_on_draft_cases_but_alu_can(): void
    {
        $document = $this->document($this->case('draft'), approved: false, stamped: false);

        $this->actingAs($this->user('hu_clerk'))->get(route('documents.download', $document))->assertForbidden();
        $this->actingAs($this->user('alu_paralegal'))->get(route('documents.download', $document))->assertOk();
    }

    public function test_guests_use_the_public_route_for_public_documents_only(): void
    {
        $case = $this->case('active');
        $public = $this->document($case, approved: true, stamped: true, contents: 'stamped order');
        $pending = $this->document($case, approved: true, stamped: false);

        $response = $this->get(route('public.documents.download', $public));
        $response->assertOk();
        $this->assertSame('stamped order', $response->streamedContent());

        $this->get(route('public.documents.download', $pending))->assertNotFound();
        $this->get(route('documents.download', $pending))->assertRedirect(route('login'));
    }

    public function test_public_documents_on_inactive_cases_are_not_public(): void
    {
        $document = $this->document($this->case('closed'), approved: true, stamped: true);

        $this->get(route('public.documents.download', $document))->assertNotFound();
    }

    public function test_missing_file_is_a_404(): void
    {
        $document = $this->document($this->case('active'), approved: true, stamped: true);
        Storage::disk('documents')->delete($document->storage_uri);

        $this->get(route('public.documents.download', $document))->assertNotFound();
    }

    public function test_files_not_yet_migrated_are_still_served_from_the_legacy_public_disk(): void
    {
        $document = $this->document($this->case('active'), approved: true, stamped: true, contents: 'legacy copy');
        Storage::disk('documents')->delete($document->storage_uri);
        Storage::disk('public')->put($document->storage_uri, 'legacy copy');

        $response = $this->get(route('public.documents.download', $document));

        $response->assertOk();
        $this->assertSame('legacy copy', $response->streamedContent());
    }

    public function test_no_public_storage_link_is_configured(): void
    {
        $this->assertSame([], config('filesystems.links'));
    }

    public function test_migrate_command_copies_legacy_files_without_overwriting(): void
    {
        $case = $this->case('active');
        $legacyOnly = $this->document($case, approved: true, stamped: true);
        Storage::disk('documents')->delete($legacyOnly->storage_uri);
        Storage::disk('public')->put($legacyOnly->storage_uri, 'from legacy');

        $alreadyStamped = $this->document($case, approved: true, stamped: true, contents: 'stamped');
        Storage::disk('public')->put($alreadyStamped->storage_uri, 'stale unstamped copy');

        $this->artisan('documents:migrate-storage', ['--dry-run' => true])->assertSuccessful();
        Storage::disk('documents')->assertMissing($legacyOnly->storage_uri);

        $this->artisan('documents:migrate-storage')->assertSuccessful();
        $this->assertSame('from legacy', Storage::disk('documents')->get($legacyOnly->storage_uri));
        $this->assertSame('stamped', Storage::disk('documents')->get($alreadyStamped->storage_uri));
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function case(string $status): CaseModel
    {
        static $n = 0;
        $n++;

        return CaseModel::forceCreate([
            'case_no' => "TEST-{$n}",
            'caption' => "Test case {$n}",
            'case_type' => 'protested',
            'status' => $status,
            'created_by_user_id' => $this->creator->id,
        ]);
    }

    private function addParty(CaseModel $case, string $email): void
    {
        $person = Person::forceCreate(['type' => 'individual', 'first_name' => 'Pat', 'email' => $email]);

        CaseParty::forceCreate(['case_id' => $case->id, 'role' => 'applicant', 'person_id' => $person->id]);
    }

    private function document(CaseModel $case, bool $approved, bool $stamped, string $contents = '%PDF-1.4 test'): Document
    {
        static $n = 0;
        $n++;
        $key = "2026/TEST {$case->id} - Test Applicant/Filing {$n} - 20261002_120000_000000.pdf";

        Storage::disk('documents')->put($key, $contents);

        return Document::forceCreate([
            'case_id' => $case->id,
            'doc_type' => 'motion',
            'original_filename' => "2026-10-02 - Filing {$n}.pdf",
            'stored_filename' => basename($key),
            'mime' => 'application/pdf',
            'size_bytes' => strlen($contents),
            'checksum' => md5($contents),
            'storage_uri' => $key,
            'uploaded_by_user_id' => $this->creator->id,
            'uploaded_at' => now(),
            'approved' => $approved,
            'stamped' => $stamped,
        ]);
    }
}
