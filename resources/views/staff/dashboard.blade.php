@extends('layouts.staff')
@section('title', 'Dashboard Staff')
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard Staff</h1>
        <p class="page-breadcrumb">Selamat datang, {{ auth()->user()->fullname }}</p>
    </div>
</div>

<div class="stats-grid mb-6">
    <div class="stats-card">
        <div class="stats-card-icon" style="background: rgba(245,158,11,0.08)">
            <svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <div class="stats-card-value text-amber-600">{{ $stats['pending_borrowings'] }}</div>
            <div class="stats-card-label">Menunggu Persetujuan</div>
        </div>
    </div>
    <div class="stats-card">
        <div class="stats-card-icon" style="background: rgba(42,154,217,0.08)">
            <svg class="w-6 h-6" style="color: var(--color-on-tertiary-container)" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 004.5 9.75v7.5a2.25 2.25 0 002.25 2.25h7.5a2.25 2.25 0 002.25-2.25v-7.5a2.25 2.25 0 00-2.25-2.25h-.75m0-3l-3-3m0 0l-3 3m3-3v11.25m6-2.25h.75a2.25 2.25 0 012.25 2.25v7.5a2.25 2.25 0 01-2.25 2.25h-7.5a2.25 2.25 0 01-2.25-2.25v-.75" />
            </svg>
        </div>
        <div>
            <div class="stats-card-value">{{ $stats['active_borrowings'] }}</div>
            <div class="stats-card-label">Sedang Dipinjam</div>
        </div>
    </div>
    <div class="stats-card">
        <div class="stats-card-icon" style="background: rgba(186,26,26,0.08)">
            <svg class="w-6 h-6" style="color: var(--color-error)" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
        </div>
        <div>
            <div class="stats-card-value" style="color: var(--color-error)">{{ $stats['overdue_borrowings'] }}</div>
            <div class="stats-card-label">Keterlambatan</div>
        </div>
    </div>
    <div class="stats-card">
        <div class="stats-card-icon" style="background: rgba(186,26,26,0.06)">
            <svg class="w-6 h-6" style="color: var(--color-error)" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <div class="stats-card-value" style="color: var(--color-error)">{{ $stats['unpaid_fines'] }}</div>
            <div class="stats-card-label">Denda Belum Dibayar</div>
        </div>
    </div>
</div>

@if($pendingBorrowings->isNotEmpty())
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Peminjaman Menunggu Persetujuan</h2>
        <a href="{{ route('staff.borrowings.index', ['status' => 'pending']) }}" class="text-xs font-medium" style="color: var(--color-secondary)">Lihat Semua →</a>
    </div>
    <div class="space-y-3">
        @foreach($pendingBorrowings as $borrowing)
            <div class="flex items-center gap-3 p-3 rounded-xl" style="background: var(--color-surface-container-low)">
                <div class="avatar avatar-sm" style="background-color: var(--color-primary-container); color: var(--color-on-primary-container)">
                    {{ strtoupper(substr($borrowing->user->fullname ?? 'U', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate">{{ $borrowing->user->fullname }}</p>
                    <p class="text-xs" style="color: var(--color-on-surface-variant)">
                        {{ $borrowing->borrowing_code }} · {{ $borrowing->items->count() }} buku
                    </p>
                </div>
                <a href="{{ route('staff.borrowings.show', $borrowing) }}" class="btn btn-ghost btn-sm">Detail</a>
            </div>
        @endforeach
    </div>
</div>
@endif

@endsection
