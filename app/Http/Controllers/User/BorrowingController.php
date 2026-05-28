<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BorrowingController extends Controller
{
    public function index(Request $request): View
    {
        $query = auth()->user()->borrowings()->with(['items.book', 'branch'])->whereNull('deleted_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $borrowings = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        return view('user.borrowings.index', compact('borrowings'));
    }

    public function show(Borrowing $borrowing): View
    {
        // Ensure user can only see their own borrowings
        abort_unless($borrowing->user_id === auth()->id(), 403);

        $borrowing->load(['items.book.category', 'branch', 'items.fine']);

        return view('user.borrowings.show', compact('borrowing'));
    }
}
