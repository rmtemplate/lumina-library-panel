@extends('layouts.user')
@section('title', 'Dashboard')
@section('content')

<div class="mb-6">
    <h1 class="text-xl font-semibold" style="color: var(--color-on-surface)">
        Selamat datang, {{ Str::words(auth()->user()->fullname, 1, '') }}!
    </h1>
    <p class="text-sm" style="color: var(--color-on-surface-variant)">
        Anggota sejak {{ auth()->user()->created_at->format('d M Y') }}
        · {{ auth()->user()->member_code }}
    </p>
</div>

{{-- Stats --}}
<div class="stats-grid mb-6">
    <div class="stats-card">
        <div class="stats-card-icon" style="background: rgba(42,154,217,0.08)">
            <svg class="w-6 h-6" style="color: var(--color-on-tertiary-container)" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
            </svg>
        </div>
        <div>
            <div class="stats-card-value">{{ $stats['active_borrowings'] }}</div>
            <div class="stats-card-label">Sedang Dipinjam</div>
        </div>
    </div>
    <div class="stats-card">
        <div class="stats-card-icon" style="background: rgba(0,108,73,0.08)">
            <svg class="w-6 h-6" style="color: var(--color-secondary)" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <div class="stats-card-value">{{ $stats['total_borrowings'] }}</div>
            <div class="stats-card-label">Total Peminjaman</div>
        </div>
    </div>
    @if($stats['overdue_borrowings'] > 0)
        <div class="stats-card">
            <div class="stats-card-icon" style="background: rgba(186,26,26,0.08)">
                <svg class="w-6 h-6" style="color: var(--color-error)" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>
            <div>
                <div class="stats-card-value" style="color: var(--color-error)">{{ $stats['overdue_borrowings'] }}</div>
                <div class="stats-card-label">Terlambat</div>
            </div>
        </div>
    @endif
    @if($stats['unpaid_fines'] > 0)
        <div class="stats-card">
            <div class="stats-card-icon" style="background: rgba(186,26,26,0.06)">
                <svg class="w-6 h-6" style="color: var(--color-error)" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182" />
                </svg>
            </div>
            <div>
                <div class="stats-card-value text-sm" style="color: var(--color-error)">
                    Rp {{ number_format($stats['unpaid_fines'], 0, ',', '.') }}
                </div>
                <div class="stats-card-label">Denda Belum Bayar</div>
            </div>
        </div>
    @endif
</div>

{{-- Active Borrowings --}}
@if($activeBorrowings->isNotEmpty())
<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title">Pinjaman Aktif</h2>
        <a href="{{ route('user.borrowings.index') }}" class="text-xs font-medium" style="color: var(--color-secondary)">Lihat Semua →</a>
    </div>
    <div class="space-y-3">
        @foreach($activeBorrowings as $borrowing)
            <div class="p-4 rounded-xl" style="background: var(--color-surface-container-low)">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-mono text-xs font-medium">{{ $borrowing->borrowing_code }}</span>
                    <span class="badge {{ $borrowing->statusBadgeClass() }}">{{ $borrowing->statusLabel() }}</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($borrowing->items as $item)
                        <span class="text-xs px-2 py-1 rounded-lg" style="background: white; color: var(--color-on-surface)">
                            {{ Str::limit($item->book->title ?? '—', 30) }}
                        </span>
                    @endforeach
                </div>
                @if($borrowing->due_date)
                    <p class="text-xs mt-2 {{ $borrowing->isOverdue() ? 'text-red-600 font-medium' : '' }}" style="{{ $borrowing->isOverdue() ? '' : 'color: var(--color-on-surface-variant)' }}">
                        Tenggat: {{ $borrowing->due_date->format('d M Y') }}
                        @if($borrowing->isOverdue()) — Terlambat! @endif
                    </p>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- Notifications --}}
@if($unreadNotifications->isNotEmpty())
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Notifikasi Baru</h2>
        <a href="{{ route('user.notifications.index') }}" class="text-xs font-medium" style="color: var(--color-secondary)">Lihat Semua →</a>
    </div>
    <div class="space-y-2">
        @foreach($unreadNotifications as $notif)
            <div class="flex items-start gap-3 p-3 rounded-xl" style="background: var(--color-surface-container-low)">
                <div class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0" style="background: var(--color-secondary)"></div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium" style="color: var(--color-on-surface)">{{ $notif->title ?? $notif->type }}</p>
                    <p class="text-xs mt-0.5" style="color: var(--color-on-surface-variant)">{{ $notif->created_at->diffForHumans() }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

@endsection
