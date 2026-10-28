<?php

namespace Tests\Unit;

use App\Services\CaseMediaFileService;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CaseMediaFileServiceTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/case-media-' . uniqid());
        File::makeDirectory($this->root . '/2026-008', 0777, true);
        config(['edocket.case_media.root' => $this->root]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_it_inspects_an_existing_media_path_and_stores_a_relative_path(): void
    {
        file_put_contents($this->root . '/2026-008/hearing.mp3', 'test audio');

        $result = (new CaseMediaFileService())->inspect('2026-008/hearing.mp3');

        $this->assertSame('2026-008/hearing.mp3', $result['relative_path']);
        $this->assertSame('hearing.mp3', $result['original_filename']);
        $this->assertSame('audio', $result['media_type']);
        $this->assertSame(10, $result['size_bytes']);
    }

    public function test_it_rejects_a_path_outside_the_configured_media_root(): void
    {
        $outside = storage_path('framework/testing/outside-media-' . uniqid() . '.mp4');
        file_put_contents($outside, 'video');

        try {
            $this->expectException(ValidationException::class);
            (new CaseMediaFileService())->inspect($outside);
        } finally {
            @unlink($outside);
        }
    }
}
