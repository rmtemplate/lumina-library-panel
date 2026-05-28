@extends('layouts.admin')
@section('title', 'Dashboard')
@section('eyebrow', 'Library')
@section('page-title', 'Dashboard Overview')
@section('page-description', 'Ringkasan operasional multi branch, status copy fisik buku, transaksi, denda, member, staff, dan aktivitas terbaru.')
@section('page-actions')
    <a href="{{ route('admin.book-items.create') }}" class="btn-secondary"><x-admin.icon name="add" class="h-4 w-4" />Add Copy</a>
    <a href="{{ route('admin.users.create') }}" class="btn-outline"><x-admin.icon name="group" class="h-4 w-4" />New User</a>
@endsection
@section('content')
<div class="space-y-8">
    <section class="rounded-xl border border-[#c5c6ce] bg-[#f5f3f6] p-4 sm:p-5">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div><p class="lumina-label text-[#006c49]">Current Data Scope</p><h2 class="mt-1 text-xl font-bold text-[#000000]">{{ $scopeLabel }}</h2></div>
            <div class="flex flex-wrap gap-2"><x-admin.badge variant="primary">{{ number_format($stats['total_branches'] ?? 0) }} Branch</x-admin.badge><x-admin.badge variant="secondary">{{ number_format($stats['total_items'] ?? 0) }} Copy</x-admin.badge><x-admin.badge variant="info">{{ number_format($stats['members'] ?? 0) }} Member</x-admin.badge><x-admin.badge variant="neutral">{{ number_format($stats['staff'] ?? 0) }} Staff</x-admin.badge></div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat-card label="Total Titles" :value="number_format($stats['total_titles'] ?? 0)" icon="menu_book" tone="primary" delta="Master catalog" />
        <x-admin.stat-card label="Available Copies" :value="number_format($stats['available_items'] ?? 0)" icon="check_circle" tone="secondary" delta="Ready to borrow" />
        <x-admin.stat-card label="Borrowed Copies" :value="number_format($stats['borrowed_items'] ?? 0)" icon="book_2" tone="info" delta="Active circulation" />
        <x-admin.stat-card label="Overdue" :value="number_format($stats['overdue_borrowings'] ?? 0)" icon="warning" tone="danger" delta="Requires action" />
        <x-admin.stat-card label="Members" :value="number_format($stats['members'] ?? 0)" icon="group" tone="primary" delta="Registered users" />
        <x-admin.stat-card label="Staff" :value="number_format($stats['staff'] ?? 0)" icon="badge" tone="secondary" delta="Branch operators" />
        <x-admin.stat-card label="Pending Borrowings" :value="number_format($stats['pending_borrowings'] ?? 0)" icon="schedule" tone="warning" delta="Need approval" />
        <x-admin.stat-card label="Unpaid Fines" :value="'Rp '.number_format((float)($stats['unpaid_fines'] ?? 0),0,',','.')" icon="payments" tone="danger" delta="Outstanding" />
    </section>

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-3">
        <div class="space-y-8 xl:col-span-2">
            <div class="lumina-card p-6">
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="lumina-section-title">Borrowing Trends</h2><p class="lumina-muted">Visualisasi status copy dan peminjaman pada scope aktif.</p></div><select class="form-select w-full sm:w-44"><option>Current Snapshot</option></select></div>
                <div class="h-[360px]"><canvas id="itemStatusChart"></canvas></div>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="lumina-card p-6"><h2 class="lumina-section-title">User Activity</h2><p class="lumina-muted">Health metric sistem.</p><div class="mt-6 space-y-4"><div><div class="mb-2 flex justify-between text-sm"><span>Active Readers</span><span class="font-bold text-[#006c49]">82%</span></div><div class="h-2 overflow-hidden rounded-full bg-[#e4e2e5]"><div class="h-full w-[82%] rounded-full bg-[#006c49]"></div></div></div><div><div class="mb-2 flex justify-between text-sm"><span>On-time Returns</span><span class="font-bold text-[#001828]">94%</span></div><div class="h-2 overflow-hidden rounded-full bg-[#e4e2e5]"><div class="h-full w-[94%] rounded-full bg-[#001828]"></div></div></div></div></div>
                <div class="relative overflow-hidden rounded-xl bg-[#051d2e] p-6 text-[#70869a]"><div class="relative z-10"><p class="text-xs font-bold uppercase tracking-[0.12em] text-[#b2c9df]">System Health</p><h2 class="mt-2 text-xl font-semibold text-white">All Systems Nominal</h2><p class="mt-2 text-sm leading-6 opacity-80">Service layer, admin scope, branch access, and API placeholders are ready.</p></div><x-admin.icon name="hub" class="absolute -bottom-6 -right-6 h-32 w-32 text-white/10" /></div>
            </div>
        </div>
        <div class="lumina-card flex flex-col overflow-hidden">
            <div class="border-b border-[#c5c6ce] p-6"><h2 class="lumina-section-title">Recent Activity</h2><p class="lumina-muted">Real-time log stream</p></div>
            <div class="max-h-[650px] flex-1 space-y-5 overflow-y-auto p-6">
                @forelse($recentActivities as $activity)
                    <div class="flex gap-4"><div class="relative"><div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#6cf8bb] text-[#002113]"><x-admin.icon name="history" class="h-5 w-5" /></div></div><div><p class="text-sm font-bold text-[#191c1d]">{{ $activity->title }}</p><p class="text-xs leading-5 text-[#44474d]">{{ $activity->subtitle ?: $activity->activity_type }}</p><p class="mt-1 text-[10px] text-[#75777e]">{{ optional($activity->created_at)->diffForHumans() }}</p></div></div>
                @empty
                    <x-admin.empty-state title="No activity yet" description="Recent system activity will appear here." icon="history" />
                @endforelse
            </div>
            <div class="border-t border-[#c5c6ce] p-4"><a href="#" class="block w-full rounded-lg py-2 text-center text-xs font-bold text-[#006c49] hover:bg-[#006c49]/5">View All Logs</a></div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="lumina-card p-6 xl:col-span-2"><div class="mb-5 flex items-center justify-between"><div><h2 class="lumina-section-title">Branch Summary</h2><p class="lumina-muted">Ringkasan per branch sesuai akses akun.</p></div><a href="{{ route('admin.branches.index') }}" class="btn-outline btn-sm">View branches</a></div><div class="grid gap-3 md:grid-cols-2">@forelse($branchSummaries as $branch)<a href="{{ route('admin.branches.show', $branch) }}" class="rounded-xl border border-[#c5c6ce] bg-white p-4 transition hover:-translate-y-0.5 hover:shadow-md"><div class="flex items-start justify-between gap-3"><div><div class="font-bold text-[#000000]">{{ $branch->name }}</div><div class="mt-1 font-mono text-xs text-[#75777e]">{{ $branch->code }}</div></div><x-admin.badge :status="$branch->is_active ? 'active' : 'inactive'" /></div><div class="mt-4 grid grid-cols-4 gap-2 text-center"><div class="rounded-lg bg-[#f5f3f6] p-2"><div class="font-bold">{{ $branch->book_items_count }}</div><div class="text-[11px] text-[#44474d]">Copy</div></div><div class="rounded-lg bg-[#f5f3f6] p-2"><div class="font-bold">{{ $branch->racks_count }}</div><div class="text-[11px] text-[#44474d]">Rack</div></div><div class="rounded-lg bg-[#f5f3f6] p-2"><div class="font-bold">{{ $branch->users_count }}</div><div class="text-[11px] text-[#44474d]">User</div></div><div class="rounded-lg bg-[#f5f3f6] p-2"><div class="font-bold">{{ $branch->borrowings_count }}</div><div class="text-[11px] text-[#44474d]">Loan</div></div></div></a>@empty <x-admin.empty-state title="Belum ada branch" description="Branch summary akan muncul setelah data tersedia." icon="location_city" /> @endforelse</div></div>
        <div class="lumina-card p-6"><div class="mb-5"><h2 class="lumina-section-title">Borrowing Status</h2><p class="lumina-muted">Komposisi status transaksi.</p></div><div class="mx-auto h-72 max-w-sm"><canvas id="borrowingStatusChart"></canvas></div></div>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.Chart) return;
    const item = document.getElementById('itemStatusChart');
    if (item) new Chart(item, {type:'bar', data:{labels:@json($charts['itemStatus']['labels']), datasets:[{label:'Copy', data:@json($charts['itemStatus']['data']), backgroundColor:['#006c49','#001828','#ba1a1a','#f59e0b','#2a9ad9'], borderRadius:12}]}, options:{maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true, grid:{color:'#edeeef'}}, x:{grid:{display:false}}}}});
    const borrowing = document.getElementById('borrowingStatusChart');
    if (borrowing) new Chart(borrowing, {type:'doughnut', data:{labels:@json($charts['borrowingStatus']['labels']), datasets:[{data:@json($charts['borrowingStatus']['data']), backgroundColor:['#f59e0b','#006c49','#2a9ad9','#ba1a1a','#75777e'], borderWidth:0}]}, options:{maintainAspectRatio:false, cutout:'68%', plugins:{legend:{position:'bottom'}}}});
});
</script>
@endpush
