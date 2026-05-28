@extends('layouts.staff')
@section('title', 'Peminjaman')
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Peminjaman</h1>
        <p class="page-breadcrumb">Kelola persetujuan dan pengembalian buku</p>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <label class="form-label">Cari</label>
            <input type="text" name="search" class="form-input" placeholder="Kode, nama peminjam..." value="{{ request('search') }}">
        </div>
        <div class="w-40">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">Semua</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="returned" {{ request('status') === 'returned' ? 'selected' : '' }}>Dikembalikan</option>
                <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Terlambat</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('staff.borrowings.index') }}" class="btn btn-ghost">Reset</a>
        @endif
    </form>
</div>

<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Peminjam</th>
                <th>Tanggal Pinjam</th>
                <th>Tenggat</th>
                <th>Status</th>
                <th class="text-right">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($borrowings as $borrowing)
                <tr>
                    <td class="font-mono text-xs">{{ $borrowing->borrowing_code }}</td>
                    <td>
                        <div>
                            <p class="text-sm font-medium">{{ $borrowing->user->fullname ?? '—' }}</p>
                            <p class="text-xs" style="color: var(--color-on-surface-variant)">{{ $borrowing->items->count() }} buku</p>
                        </div>
                    </td>
                    <td class="text-sm">{{ $borrowing->borrow_date?->format('d M Y') ?? '—' }}</td>
                    <td class="text-sm {{ $borrowing->isOverdue() ? 'text-red-600 font-medium' : '' }}">
                        {{ $borrowing->due_date?->format('d M Y') ?? '—' }}
                    </td>
                    <td>
                        <span class="badge {{ $borrowing->statusBadgeClass() }}">{{ $borrowing->statusLabel() }}</span>
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('staff.borrowings.show', $borrowing) }}" class="btn btn-ghost btn-sm">Detail</a>
                            @if($borrowing->status === 'pending')
                                <form method="POST" action="{{ route('staff.borrowings.approve', $borrowing) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-secondary btn-sm">Setujui</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state py-10">
                            <p class="text-sm">Tidak ada peminjaman ditemukan</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if($borrowings->hasPages())
        <div class="pagination-wrapper">
            <div class="text-xs" style="color: var(--color-on-surface-variant)">
                {{ $borrowings->firstItem() }}–{{ $borrowings->lastItem() }} dari {{ $borrowings->total() }}
            </div>
            {{ $borrowings->links() }}
        </div>
    @endif
</div>

@endsection
