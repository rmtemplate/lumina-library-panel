@extends('layouts.admin')
@section('title','Branch Detail')
@section('page-title',$branch->name)
@section('page-description','Detail cabang, koordinat, map preview, stok, rack, user, dan transaksi pada branch ini.')
@section('page-actions')@if(auth()->user()?->isSuperAdmin())<a href="{{ route('admin.branches.edit',$branch) }}" class="btn-primary"><x-admin.icon name="edit" class="h-4 w-4" />Edit Branch</a>@endif<a href="{{ route('admin.branches.index') }}" class="btn-outline">Back</a>@endsection
@push('styles')<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />@endpush
@section('content')
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <div class="lumina-card overflow-hidden">
            @if($branch->image_url)<img src="{{ $branch->image_url }}" class="h-64 w-full object-cover" alt="{{ $branch->name }}">@endif
            <div class="p-6"><h2 class="lumina-section-title">Branch Information</h2><p class="mt-3 text-sm leading-7 text-[#44474d]">{{ $branch->address ?: 'No address.' }}</p><dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@foreach(['Code'=>$branch->code,'Phone'=>$branch->phone,'Email'=>$branch->email,'Schedule'=>$branch->schedule_description,'Open Time'=>$branch->open_time,'Close Time'=>$branch->close_time] as $label=>$value)<div class="rounded-xl bg-[#f5f3f6] p-4"><dt class="text-xs font-semibold uppercase tracking-[0.08em] text-[#75777e]">{{ $label }}</dt><dd class="mt-1 text-sm font-bold">{{ $value ?: '-' }}</dd></div>@endforeach</dl></div>
        </div>
        <div class="lumina-card p-6"><div class="mb-5"><h2 class="lumina-section-title">Location Preview</h2><p class="lumina-muted">Koordinat: {{ $branch->latitude ?: '-' }}, {{ $branch->longitude ?: '-' }}</p></div><x-admin.map-picker :latitude="$branch->latitude" :longitude="$branch->longitude" :readonly="true" height="380px" /></div>
    </div>
    <div class="space-y-6"><x-admin.stat-card label="Book Copies" :value="$branch->bookItems->count()" icon="menu_book" tone="primary" /><x-admin.stat-card label="Racks" :value="$branch->racks->count()" icon="inventory_2" tone="secondary" /><x-admin.stat-card label="Users" :value="$branch->users->count()" icon="group" tone="info" /><div class="lumina-card p-6"><h2 class="lumina-section-title">Status</h2><div class="mt-4"><x-admin.badge :status="$branch->is_active ? 'active' : 'inactive'">{{ $branch->is_active ? 'Active' : 'Inactive' }}</x-admin.badge></div></div></div>
</div>
@endsection
@push('scripts')<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>@endpush
