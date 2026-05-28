<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Fine;
use App\Models\FinePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FineController extends Controller
{
    public function index(Request $request): View
    {
        $query = Fine::with(['user', 'borrowingItem.book'])->whereNull('deleted_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $fines = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('staff.fines.index', compact('fines'));
    }

    public function show(Fine $fine): View
    {
        $fine->load(['user', 'borrowingItem.book', 'payments']);

        return view('staff.fines.show', compact('fine'));
    }

    public function verifyPayment(Request $request, Fine $fine): RedirectResponse
    {
        $request->validate([
            'payment_id' => ['required', 'uuid', 'exists:fine_payments,id'],
            'action'     => ['required', 'in:verified,rejected'],
            'notes'      => ['nullable', 'string'],
        ]);

        $payment = FinePayment::findOrFail($request->payment_id);

        if ($request->action === 'verified') {
            $payment->update([
                'status'      => 'verified',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

            $fine->update([
                'status'   => Fine::PAID,
                'paid_at'  => now(),
                'paid_amount' => $payment->amount,
            ]);
        } else {
            $payment->update(['status' => 'rejected']);
        }

        return back()->with('success', 'Pembayaran denda berhasil diverifikasi.');
    }
}
