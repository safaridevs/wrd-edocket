<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Download or preview a document's file.
     *
     * Anyone may fetch a publicly available document (accepted, stamped, active case). Otherwise
     * the user must be allowed to open the document's case page, which already lists every
     * document on the case to whoever may open it — so this grants nothing the pages don't show.
     */
    public function view(?User $user, Document $document): bool
    {
        if ($document->isPubliclyAvailable()) {
            return true;
        }

        if (!$user || !$document->case) {
            return false;
        }

        return $user->canViewCase($document->case);
    }
}
