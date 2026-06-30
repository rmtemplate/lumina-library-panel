@extends('layouts.admin')
@section('title','Physical Copies')
@section('page-title','Physical Copies')
@section('page-description','Kelola copy fisik per branch, rak, barcode, dan status operasional.')
@section('page-actions')<a href="{{ route('admin.book-items.create') }}" class="btn-primary"><x-admin.icon name="plus" class="h-4 w-4" />Add Copy</a>@endsection
@section('content')
<x-admin.filters search-placeholder="Search item code, barcode, title">
    @if(auth()->user()?->isSuperAdmin())<select name="branch_id" class="form-select w-full md:w-56"><option value="">All branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(request('branch_id')==$branch->id)>{{ $branch->name }}</option>@endforeach</select>@endif
    <select name="book_id" class="form-select w-full md:w-56"><option value="">All titles</option>@foreach($books as $book)<option value="{{ $book->id }}" @selected(request('book_id')==$book->id)>{{ $book->title }}</option>@endforeach</select>
    <select name="status" class="form-select w-full md:w-44"><option value="">All status</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status')===$status->value)>{{ ucfirst($status->value) }}</option>@endforeach</select>
</x-admin.filters>

{{-- Bulk QR form lives outside the table so the row delete-forms never nest
     inside it. Row checkboxes attach to it via the HTML `form` attribute. --}}
<form id="qr-bulk-form" method="POST" class="mb-3 flex flex-wrap items-center gap-2">
    @csrf
    <span class="text-sm text-[#75777e]"><span id="qr-selected-count">0</span> selected</span>
    <button type="submit" formaction="{{ route('admin.book-items.qr.bulk-print') }}" formtarget="_blank" class="btn-secondary js-bulk-action" disabled><x-admin.icon name="print" class="h-4 w-4" />Print Selected</button>
    <button type="submit" formaction="{{ route('admin.book-items.qr.bulk-zip') }}" class="btn-secondary js-bulk-action" disabled><x-admin.icon name="arrow-down-tray" class="h-4 w-4" />Download ZIP</button>
</form>

<div class="table-card overflow-x-auto"><table class="modern-table min-w-[1140px]"><thead><tr><th class="w-10"><input type="checkbox" id="qr-select-all" class="form-checkbox" /></th><th>Copy</th><th>Title</th><th>Branch</th><th>Rack</th><th>Status</th><th>Active</th><th class="text-right">Action</th></tr></thead><tbody>@forelse($items as $item)<tr><td><input type="checkbox" name="ids[]" value="{{ $item->id }}" form="qr-bulk-form" class="form-checkbox js-qr-select" /></td><td><div class="font-bold text-[#031632]">{{ $item->item_code }}</div><div class="font-mono text-xs text-[#75777e]">{{ $item->barcode ?: '-' }}</div></td><td><div class="font-bold">{{ $item->book?->title }}</div><div class="text-xs text-[#44474d]">{{ $item->book?->author }}</div></td><td>{{ $item->branch?->name }}</td><td><div>{{ $item->rack?->rack_code ?? '-' }}</div><div class="text-xs text-[#75777e]">{{ $item->rack?->name }}</div></td><td><x-admin.badge :status="$item->status" /></td><td><x-admin.badge :status="$item->is_active ? 'active' : 'inactive'" /></td><td class="text-right"><x-admin.action-menu><x-admin.action-link :href="route('admin.book-items.qr.preview',$item)" icon="qr" class="js-qr-modal" data-qr-img="{{ route('admin.book-items.qr.png',[$item,'inline'=>1]) }}" data-qr-download="{{ route('admin.book-items.qr.png',$item) }}" data-qr-detail="{{ route('admin.book-items.qr.preview',$item) }}" data-qr-print="{{ route('admin.book-items.qr.print',$item) }}" data-qr-title="{{ $item->book?->title }}" data-qr-cover="{{ $item->book?->cover_url }}" data-qr-code="{{ $item->item_code }}" data-qr-barcode="{{ $item->barcode }}" data-qr-branch="{{ $item->branch?->name }}">View QR</x-admin.action-link><x-admin.action-link :href="route('admin.book-items.edit',$item)" icon="pencil-square">Edit</x-admin.action-link><form method="POST" action="{{ route('admin.book-items.destroy',$item) }}" class="js-delete-form" data-delete-label="{{ $item->item_code }}">@csrf @method('DELETE')<x-admin.action-button icon="trash" danger>Delete</x-admin.action-button></form></x-admin.action-menu></td></tr>@empty<tr><td colspan="8"><x-admin.empty-state title="No physical copies found" description="Add item-level copies so inventory and borrowing tracking become accurate." icon="archive-box" /></td></tr>@endforelse</tbody></table></div><x-admin.pagination :paginator="$items" />

{{-- Book QR modal — populated client-side from each row's data-qr-* attrs. --}}
<div id="qr-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true" aria-label="Book QR">
        <div class="flex items-center justify-between border-b border-[#e6e8ef] px-5 py-4">
            <h3 class="text-base font-black text-[#031632]">Book QR</h3>
            <button type="button" data-qr-close class="rounded-lg p-1.5 text-[#75777e] transition hover:bg-[#f3f4f5] hover:text-[#031632]"><x-admin.icon name="x-mark" class="h-5 w-5" /></button>
        </div>
        <div class="grid gap-5 p-5 sm:grid-cols-[200px_1fr]">
            <div class="flex justify-center">
                <img id="qr-modal-img" src="" alt="QR code" class="h-48 w-48 rounded-xl border border-[#e6e8ef] bg-white p-2" />
            </div>
            <div class="space-y-3">
                <div class="flex gap-3">
                    <img id="qr-modal-cover" src="" alt="Cover" class="h-20 w-14 rounded-lg border border-[#e6e8ef] object-cover" />
                    <div class="min-w-0">
                        <div id="qr-modal-title" class="truncate font-black text-[#031632]"></div>
                        <div class="mt-1 text-xs text-[#75777e]">Item Code</div>
                        <div id="qr-modal-code" class="font-mono text-sm font-bold text-[#031632]"></div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div><div class="text-xs text-[#75777e]">Barcode</div><div id="qr-modal-barcode" class="font-mono"></div></div>
                    <div><div class="text-xs text-[#75777e]">Branch</div><div id="qr-modal-branch"></div></div>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-[#eafaf0] px-2 py-1 text-xs font-bold text-[#0a7d3f]">Permanent · never expires</span>
            </div>
        </div>
        <div class="flex flex-wrap justify-end gap-2 border-t border-[#e6e8ef] px-5 py-4">
            <button type="button" data-qr-close class="btn-secondary">Close</button>
            <a id="qr-modal-detail" href="#" class="btn-secondary">View Detail</a>
            <button type="button" id="qr-modal-print" class="btn-secondary"><x-admin.icon name="print" class="h-4 w-4" />Print QR</button>
            <a id="qr-modal-download" href="#" class="btn-primary"><x-admin.icon name="arrow-down-tray" class="h-4 w-4" />Download PNG</a>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    // ── Bulk selection ───────────────────────────────────────────────
    const selectAll = document.getElementById('qr-select-all');
    const boxes = Array.from(document.querySelectorAll('.js-qr-select'));
    const counter = document.getElementById('qr-selected-count');
    const actions = Array.from(document.querySelectorAll('.js-bulk-action'));

    function sync() {
        const checked = boxes.filter(b => b.checked).length;
        if (counter) counter.textContent = checked;
        actions.forEach(btn => btn.disabled = checked === 0);
        if (selectAll) selectAll.checked = checked > 0 && checked === boxes.length;
    }

    if (selectAll) selectAll.addEventListener('change', () => {
        boxes.forEach(b => { b.checked = selectAll.checked; });
        sync();
    });
    boxes.forEach(b => b.addEventListener('change', sync));
    sync();

    // ── QR modal ─────────────────────────────────────────────────────
    const modal = document.getElementById('qr-modal');
    if (!modal) return;

    function setText(id, value) { modal.querySelector(id).textContent = value || '—'; }

    function openModal(el) {
        modal.querySelector('#qr-modal-img').src = el.dataset.qrImg || '';
        const cover = modal.querySelector('#qr-modal-cover');
        if (el.dataset.qrCover) { cover.src = el.dataset.qrCover; cover.style.display = ''; }
        else { cover.removeAttribute('src'); cover.style.display = 'none'; }
        setText('#qr-modal-title', el.dataset.qrTitle);
        setText('#qr-modal-code', el.dataset.qrCode);
        setText('#qr-modal-barcode', el.dataset.qrBarcode);
        setText('#qr-modal-branch', el.dataset.qrBranch);
        modal.querySelector('#qr-modal-detail').href = el.dataset.qrDetail || '#';
        modal.querySelector('#qr-modal-download').href = el.dataset.qrDownload || '#';
        modal.dataset.printUrl = el.dataset.qrPrint || '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.querySelector('#qr-modal-img').src = '';
    }

    document.querySelectorAll('.js-qr-modal').forEach(el => {
        el.addEventListener('click', e => {
            e.preventDefault();
            const menu = el.closest('details[data-action-menu]');
            if (menu) menu.removeAttribute('open');
            openModal(el);
        });
    });

    modal.querySelectorAll('[data-qr-close]').forEach(b => b.addEventListener('click', closeModal));
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('flex')) closeModal(); });

    modal.querySelector('#qr-modal-print').addEventListener('click', () => {
        if (modal.dataset.printUrl) window.open(modal.dataset.printUrl, '_blank');
    });
})();
</script>
@endpush
@endsection
