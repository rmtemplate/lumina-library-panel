<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use App\Models\Fine;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $stats = [
            'active_borrowings' => $user->borrowings()->where('status', Borrowing::ACTIVE)->count(),
            'overdue_borrowings' => $user->borrowings()->where('status', Borrowing::OVERDUE)->count(),
            'total_borrowings'  => $user->borrowings()->count(),
            'unpaid_fines'      => $user->fines()->where('status', Fine::UNPAID)->sum('fine_amount'),
        ];

        $activeBorrowings = $user->borrowings()
            ->with(['items.book', 'branch'])
            ->whereIn('status', [Borrowing::ACTIVE, Borrowing::PENDING])
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $unreadNotifications = $user->notifications()
            ->where('is_read', false)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('user.dashboard', compact('stats', 'activeBorrowings', 'unreadNotifications'));
    }
}
