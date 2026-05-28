@extends('layouts.admin')
@section('title','Fines')
@section('page-title','Fines')
@section('page-description','Kelola dan monitoring denda keterlambatan pengembalian buku.')
@section('content')
<div class="mb-5 grid gap-4 sm:grid-cols-3">
    <x-admin.stat-card label="Unpaid" value="Rp {{ number_format($summary['unpaid'] ?? 0,0,',','.') }}" icon="banknotes" tone="warning" />
    <x-admin.stat-card label="Paid" value="Rp {{ number_format($summary['paid'] ?? 0,0,',','.') }}" icon="check-circle" tone="secondary" />
    <x-admin.stat-card label="Waived" value="Rp {{ number_format($summary['waived'] ?? 0,0,',','.') }}" icon="sparkles" tone="info" />
</div>
<x-admin.filters search-placeholder="Search user name or email"><select name="status" class="form-select w-full md:w-44"><option value="">All status</option>@foreach(['unpaid','paid','waived'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select></x-admin.filters>
<div class="table-card overflow-x-auto"><table class="modern-table min-w-[860px]"><thead><tr><th>User</th><th>Book</th><th>Amount</th><th>Overdue</th><th>Status</th><th class="text-right">Action</th></tr></thead><tbody>
@forelse($fines as $fine)<tr><td><div class="font-bold">{{ $fine->user?->fullname ?? '-' }}</div><div class="text-xs text-[#44474d]">{{ $fine->user?->email ?? '-' }}</div></td><td>{{ $fine->borrowingItem?->bookItem?->book?->title ?? '-' }}</td><td><div class="font-bold">Rp {{ number_format($fine->fine_amount,0,',','.') }}</div><div class="text-xs text-[#75777e]">Paid: Rp {{ number_format($fine->paid_amount ?? 0,0,',','.') }}</div></td><td>{{ $fine->overdue_days }} days</td><td><x-admin.badge :status="$fine->status" /></td><td class="text-right"><x-admin.action-menu><x-admin.action-link :href="route('admin.fines.show',$fine)" icon="eye">Detail</x-admin.action-link>@if($fine->status === 'unpaid')<form method="POST" action="{{ route('admin.fines.waive',$fine) }}">@csrf @method('PATCH')<input type="hidden" name="waived_reason" value="Waived from list action"><x-admin.action-button icon="sparkles">Waive</x-admin.action-button></form>@endif</x-admin.action-menu></td></tr>@empty<tr><td colspan="6"><x-admin.empty-state title="No fines found" description="Fine data will appear when overdue transactions are recorded." icon="banknotes" /></td></tr>@endforelse
</tbody></table></div><x-admin.pagination :paginator="$fines" />
@endsection
