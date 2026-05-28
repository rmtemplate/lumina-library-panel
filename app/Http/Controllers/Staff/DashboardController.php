<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use App\Models\Fine;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'pending_borrowings' => Borrowing::where('status', Borrowing::PENDING)->count(),
            'active_borrowings'  => Borrowing::where('status', Borrowing::ACTIVE)->count(),
            'overdue_borrowings' => Borrowing::where('status', Borrowing::OVERDUE)->count(),
            'unpaid_fines'       => Fine::where('status', Fine::UNPAID)->count(),
        ];

        $pendingBorrowings = Borrowing::with(['user', 'branch', 'items.book'])
            ->where('status', Borrowing::PENDING)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('staff.dashboard', compact('stats', 'pendingBorrowings'));
    }
}
