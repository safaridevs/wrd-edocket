<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\User;
use App\Models\CaseParty;

class WorkflowService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function transmitToWRAP(array $materials, User $wrdExpert): void
    {
        $wrapDirector = User::where('role', 'wrap_director')->first();
        
        if ($wrapDirector) {
            $this->notificationService->notify(
                $wrapDirector,
                'materials_received',
                'Materials from WRD',
                'New materials received from District Office for review.',
            );
        }
    }

    public function forwardToALU(array $package, User $wrapDirector): void
    {
        $aluManager = User::where('role', 'alu_managing_atty')->first();
        
        if ($aluManager) {
            $this->notificationService->notify(
                $aluManager,
                'package_forwarded',
                'Package from WRAP',
                'Package forwarded from WRAP Director for assignment.',
            );
        }
    }

    public function assignStaff(CaseModel $case, User $aluManager, array $assignments): void
    {
        $recipients = [];

        foreach ($assignments as $assignment) {
            $user = User::find($assignment['user_id']);
            if ($user) {
                $recipients[] = $user;
            }
        }

        if (!empty($recipients)) {
            $this->notificationService->notifyMultiple(
                $recipients,
                'case_assignment',
                'Case Assignment',
                "You have been assigned to case {$case->case_number}.",
                $case
            );
        }
    }

    public function activateCase(CaseModel $case, User $huUser): bool
    {
        if (!$huUser->canAcceptFilings()) {
            return false;
        }

        $case->update(['status' => 'active']);

        // Notify all served parties
        $this->notifyServedParties($case, 'case_activated', 'Case Activated', 'Case has been activated and is now accepting filings.');

        return true;
    }

    public function notifyServedParties(CaseModel $case, string $type, string $title, string $message): void
    {
        $servedParties = $case->parties()->where('is_served', true)->get();
        $recipients = [];

        foreach ($servedParties as $party) {
            if ($party->email && $user = User::where('email', $party->email)->first()) {
                $recipients[] = $user;
            }
        }

        if (!empty($recipients)) {
            $this->notificationService->notifyMultiple($recipients, $type, $title, $message, $case);
        }
    }
}
