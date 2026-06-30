@extends('layouts.admin')
@section('title','QR Label')
@section('page-title','QR Label')
@section('page-description','QR untuk ditempel pada copy fisik. Dipindai oleh Universal Scanner aplikasi staff.')
@section('page-actions')
    <a href="{{ route('admin.book-items.index') }}" class="btn-secondary"><x-admin.icon name="arrow_back" class="h-4 w-4" />Back</a>
@endsection
@section('content')
<div class="grid gap-6 lg:grid-cols-[360px_1fr]">
    <div class="table-card flex flex-col items-center gap-4 p-6 text-center">
        <img src="{{ $qrDataUri }}" alt="QR {{ $item->item_code }}" class="h-64 w-64 rounded-2xl border border-[#e6e8ef] bg-white p-2" />
        <div>
            <div class="text-lg font-black text-[#031632]">{{ $item->item_code }}</div>
            <div class="font-mono text-xs text-[#75777e]">{{ $item->barcode ?: '—' }}</div>
        </div>
        <div class="flex flex-wrap justify-center gap-2">
            <a href="{{ route('admin.book-items.qr.png', $item) }}" class="btn-primary"><x-admin.icon name="arrow-down-tray" class="h-4 w-4" />PNG</a>
            <a href="{{ route('admin.book-items.qr.svg', $item) }}" class="btn-secondary"><x-admin.icon name="arrow-down-tray" class="h-4 w-4" />SVG</a>
            <a href="{{ route('admin.book-items.qr.print', $item) }}" target="_blank" class="btn-secondary"><x-admin.icon name="print" class="h-4 w-4" />Print</a>
        </div>
    </div>

    <div class="table-card p-6">
        <div class="mb-4 flex items-center gap-4">
            @if($item->book?->cover_url)
                <img src="{{ $item->book->cover_url }}" alt="Cover" class="h-20 w-14 rounded-lg border border-[#e6e8ef] object-cover" />
            @endif
            <h3 class="text-sm font-black uppercase tracking-wide text-[#75777e]">Detail Copy</h3>
        </div>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div><dt class="text-xs text-[#75777e]">Judul</dt><dd class="font-bold text-[#031632]">{{ $item->book?->title ?? '—' }}</dd></div>
            <div><dt class="text-xs text-[#75777e]">Penulis</dt><dd class="text-[#44474d]">{{ $item->book?->author ?? '—' }}</dd></div>
            <div><dt class="text-xs text-[#75777e]">Item Code</dt><dd class="font-mono text-[#031632]">{{ $item->item_code }}</dd></div>
            <div><dt class="text-xs text-[#75777e]">Barcode</dt><dd class="font-mono text-[#031632]">{{ $item->barcode ?: '—' }}</dd></div>
            <div><dt class="text-xs text-[#75777e]">Branch</dt><dd class="text-[#44474d]">{{ $item->branch?->name ?? '—' }}</dd></div>
            <div><dt class="text-xs text-[#75777e]">Rack</dt><dd class="text-[#44474d]">{{ $item->rack?->rack_code ?? '—' }}</dd></div>
        </dl>
        <p class="mt-6 rounded-xl bg-[#f4f6fb] p-4 text-xs text-[#44474d]">
            QR ini meng-encode <span class="font-mono font-bold">{{ $item->item_code }}</span> dan dibaca oleh Universal Scanner
            aplikasi staff (jalur pencocokan item_code / barcode). Cetak pada label dan tempel di buku fisik.
        </p>
    </div>
</div>
@endsection
