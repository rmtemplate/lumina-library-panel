<?php

use App\Models\Borrowing;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{userId}', function ($user, string $userId) {
    return (string) $user->id === (string) $userId;
});

/**
 * Private channel for a specific borrowing transaction.
 * Authorized for: the member who owns it, or staff assigned to the same branch.
 */
Broadcast::channel('borrowing.{borrowingId}', function ($user, string $borrowingId) {
    $borrowing = Borrowing::select('id', 'user_id', 'branch_id', 'library_branch_id')
        ->find($borrowingId);

    if (! $borrowing) {
        return false;
    }

    if ((string) $borrowing->user_id === (string) $user->id) {
        return true;
    }

    if ($user->isStaff() || $user->isAdmin()) {
        $borrowingBranchId = $borrowing->branch_id ?: $borrowing->library_branch_id;
        $userBranchId = $user->staff_library_id ?: $user->branch_id;

        return (string) $userBranchId === (string) $borrowingBranchId;
    }

    return false;
});

/**
 * Private channel for everything staff-dashboard-wide at a branch — borrow/
 * return/registration events that aren't scoped to one specific user or
 * borrowing. Every staff member assigned to that branch can subscribe.
 */
Broadcast::channel('branch.{branchId}', function ($user, string $branchId) {
    if (! ($user->isStaff() || $user->isAdmin())) {
        return false;
    }

    $userBranchId = $user->staff_library_id ?: $user->branch_id;

    return (string) $userBranchId === (string) $branchId;
});