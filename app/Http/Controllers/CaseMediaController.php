<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CaseMedia;
use App\Models\CaseModel;
use App\Services\CaseMediaFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class CaseMediaController extends Controller
{
    public function __construct(private CaseMediaFileService $files)
    {
    }

    public function store(Request $request, CaseModel $case)
    {
        $this->authorizeManagement($case);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'media_path' => ['required', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'received_at' => ['nullable', 'date'],
        ]);
        $file = $this->files->inspect($validated['media_path']);

        if ($case->media()->where('relative_path', $file['relative_path'])->exists()) {
            return back()->withInput()->withErrors([
                'media_path' => 'This media file is already attached to the case.',
            ]);
        }

        $media = $case->media()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'received_at' => $validated['received_at'] ?? null,
            'relative_path' => $file['relative_path'],
            'original_filename' => $file['original_filename'],
            'media_type' => $file['media_type'],
            'mime_type' => $file['mime_type'],
            'size_bytes' => $file['size_bytes'],
            'attached_by_user_id' => Auth::id(),
        ]);

        AuditLog::log('attach_case_media', Auth::user(), $case, [
            'case_media_id' => $media->id,
            'title' => $media->title,
            'media_type' => $media->media_type,
            'relative_path' => $media->relative_path,
        ]);

        return back()->with('success', 'Media attached successfully.');
    }

    public function view(CaseModel $case, CaseMedia $media)
    {
        $this->authorizeAccess($case, $media);

        return Response::file($this->files->path($media), [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="' . addcslashes($media->original_filename, '"\\') . '"',
        ]);
    }

    public function download(CaseModel $case, CaseMedia $media)
    {
        $this->authorizeAccess($case, $media);
        $this->logAccess($case, $media, 'download_case_media');

        return Response::download($this->files->path($media), $media->original_filename, [
            'Content-Type' => $media->mime_type,
        ]);
    }

    public function publicView(CaseModel $case, CaseMedia $media)
    {
        $this->authorizePublicAccess($case, $media);

        return Response::file($this->files->path($media), [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="' . addcslashes($media->original_filename, '"\\') . '"',
        ]);
    }

    public function publicDownload(CaseModel $case, CaseMedia $media)
    {
        $this->authorizePublicAccess($case, $media);

        return Response::download($this->files->path($media), $media->original_filename, [
            'Content-Type' => $media->mime_type,
        ]);
    }

    public function destroy(CaseModel $case, CaseMedia $media)
    {
        $this->authorizeManagement($case);
        $this->ensureBelongsToCase($case, $media);

        $media->forceFill([
            'removed_at' => now(),
            'removed_by_user_id' => Auth::id(),
        ])->save();
        $media->delete();

        AuditLog::log('remove_case_media', Auth::user(), $case, [
            'case_media_id' => $media->id,
            'title' => $media->title,
            'relative_path' => $media->relative_path,
        ]);

        return back()->with('success', 'Media removed from the case. The physical file was not deleted.');
    }

    private function authorizeManagement(CaseModel $case): void
    {
        abort_unless(Auth::user()?->isHearingUnit() && $case->status === 'active', 403);
    }

    private function authorizeAccess(CaseModel $case, CaseMedia $media): void
    {
        $this->ensureBelongsToCase($case, $media);
        abort_unless($case->status === 'active' && Auth::user()?->canAccessCase($case), 403);
    }

    private function authorizePublicAccess(CaseModel $case, CaseMedia $media): void
    {
        $this->ensureBelongsToCase($case, $media);
        abort_unless($case->status === 'active', 404);
    }

    private function ensureBelongsToCase(CaseModel $case, CaseMedia $media): void
    {
        abort_unless((int) $media->case_id === (int) $case->id, 404);
    }

    private function logAccess(CaseModel $case, CaseMedia $media, string $action): void
    {
        AuditLog::log($action, Auth::user(), $case, [
            'case_media_id' => $media->id,
            'title' => $media->title,
        ]);
    }
}
