<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BorrowingController extends Controller
{
    public function index(Request $request): View
    {
        $query = Borrowing::with(['user', 'branch', 'items'])->whereNull('deleted_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('borrowing_code', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($uq) => $uq->where('fullname', 'like', "%{$search}%")
                      ->orWhere('member_code', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $borrowings = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('staff.borrowings.index', compact('borrowings'));
    }

    public function show(Borrowing $borrowing): View
    {
        $borrowing->load(['user', 'branch', 'items.book', 'items.fine', 'approvedBy']);

        return view('staff.borrowings.show', compact('borrowing'));
    }

    public function approve(Borrowing $borrowing): RedirectResponse
    {
        if ($borrowing->status !== Borrowing::PENDING) {
            return back()->with('error', 'Hanya peminjaman berstatus "Menunggu" yang dapat disetujui.');
        }

        $borrowing->update([
            'status'      => Borrowing::ACTIVE,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', "Peminjaman {$borrowing->borrowing_code} berhasil disetujui.");
    }

    public function processReturn(Request $request, Borrowing $borrowing): RedirectResponse
    {
        $request->validate([
            'return_condition' => ['required', 'in:good,damaged,lost'],
            'notes'            => ['nullable', 'string'],
        ]);

        $borrowing->update([
            'status'      => Borrowing::RETURNED,
            'return_date' => now()->toDateString(),
        ]);

        foreach ($borrowing->items as $item) {
            $item->update([
                'status'           => 'returned',
                'return_date'      => now()->toDateString(),
                'return_condition' => $request->return_condition,
                'notes'            => $request->notes,
            ]);
        }

        return back()->with('success', "Pengembalian buku berhasil diproses.");
    }

    public function scan(): View
    {
        return view('staff.borrowings.scan');
    }
}
