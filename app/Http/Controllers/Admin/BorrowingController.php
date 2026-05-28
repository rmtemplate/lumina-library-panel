<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use App\Services\Admin\BorrowingService;
use App\Services\Admin\BranchAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BorrowingController extends Controller
{
    public function index(Request $request, BorrowingService $service): View
    {
        return view('admin.borrowings.index', ['borrowings' => $service->paginate($request->only(['search', 'status', 'per_page']), $request->user())]);
    }

    public function show(Request $request, Borrowing $borrowing, BranchAccessService $access): View
    {
        $access->assertCanAccessBranch($request->user(), $borrowing->branch_id);
        return view('admin.borrowings.show', ['borrowing' => $borrowing->load(['user.roles', 'branch', 'items.bookItem.book.category', 'items.fines', 'qrCodes', 'approvedBy'])]);
    }

    public function approve(Request $request, Borrowing $borrowing, BorrowingService $service): RedirectResponse
    {
        $updated = $service->approve($borrowing, $request->user());
        return back()->with('success', "Peminjaman {$updated->borrowing_code} berhasil disetujui.");
    }

    public function cancel(Request $request, Borrowing $borrowing, BorrowingService $service): RedirectResponse
    {
        $updated = $service->cancel($borrowing, $request->user());
        return back()->with('success', "Peminjaman {$updated->borrowing_code} berhasil dibatalkan.");
    }
}
