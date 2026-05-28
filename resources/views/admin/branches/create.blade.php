@extends('layouts.admin')
@section('title', isset($branch) ? 'Edit Branch' : 'Create Branch')
@section('page-title', isset($branch) ? 'Edit Branch' : 'Create Branch')
@section('page-description','Kelola identitas, lokasi interaktif, kontak, dan jadwal operasional cabang.')
@section('page-actions')<a href="{{ route('admin.branches.index') }}" class="btn-outline">Back</a>@endsection
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush
@section('content')
<form method="POST" action="{{ isset($branch) ? route('admin.branches.update',$branch) : route('admin.branches.store') }}" class="space-y-6" enctype="multipart/form-data">
    @csrf @isset($branch) @method('PUT') @endisset
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(380px,0.95fr)]">
        <div class="space-y-6">
            <div class="lumina-card p-6">
                <div class="mb-5"><h2 class="lumina-section-title">Branch Identity</h2><p class="lumina-muted">Informasi utama cabang perpustakaan.</p></div>
                <div class="grid gap-5 md:grid-cols-2">
                    <div><label class="form-label">Code</label><input name="code" value="{{ old('code',$branch->code ?? '') }}" class="form-input" required></div>
                    <div><label class="form-label">Name</label><input name="name" value="{{ old('name',$branch->name ?? '') }}" class="form-input" required></div>
                    <div><label class="form-label">Phone</label><input name="phone" value="{{ old('phone',$branch->phone ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">Email</label><input name="email" type="email" value="{{ old('email',$branch->email ?? '') }}" class="form-input"></div>
                    <div class="md:col-span-2"><label class="form-label">Branch Image</label><input type="file" name="image_file" accept="image/*" class="form-input"><p class="form-help">Admin wajib upload gambar. URL hanya dipakai untuk seeder/dummy data.</p>@if(isset($branch) && $branch->image_url)<img src="{{ $branch->image_url }}" class="mt-3 h-44 w-full rounded-xl object-cover" alt="{{ $branch->name }}">@endif</div>
                    <div class="md:col-span-2"><label class="form-label">Address</label><textarea name="address" class="form-textarea">{{ old('address',$branch->address ?? '') }}</textarea></div>
                    <div class="md:col-span-2"><label class="form-label">Description</label><textarea name="description" class="form-textarea">{{ old('description',$branch->description ?? '') }}</textarea></div>
                </div>
            </div>
            <div class="lumina-card p-6">
                <div class="mb-5"><h2 class="lumina-section-title">Operational Schedule</h2><p class="lumina-muted">Jam buka dan status layanan.</p></div>
                <div class="grid gap-5 md:grid-cols-2">
                    <div><label class="form-label">Open Time</label><input name="open_time" type="time" value="{{ old('open_time',$branch->open_time ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">Close Time</label><input name="close_time" type="time" value="{{ old('close_time',$branch->close_time ?? '') }}" class="form-input"></div>
                    <div class="md:col-span-2"><label class="form-label">Schedule Description</label><input name="schedule_description" value="{{ old('schedule_description',$branch->schedule_description ?? '') }}" class="form-input" placeholder="Senin - Jumat, 08.00 - 20.00"></div>
                    <div class="md:col-span-2"><label class="inline-flex items-center gap-2 text-sm font-semibold text-[#44474d]"><input type="checkbox" name="is_active" value="1" class="form-check" @checked(old('is_active',$branch->is_active ?? true))> Active branch</label></div>
                </div>
            </div>
        </div>
        <div class="lumina-card p-6">
            <div class="mb-5"><h2 class="lumina-section-title">Interactive Location Picker</h2><p class="lumina-muted">Cari lokasi, klik map, atau drag marker untuk mengisi koordinat otomatis.</p></div>
            <x-admin.map-picker :latitude="$branch->latitude ?? null" :longitude="$branch->longitude ?? null" :address="$branch->address ?? ''" />
        </div>
    </div>
    <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row"><a href="{{ route('admin.branches.index') }}" class="btn-outline">Cancel</a><button class="btn-primary" data-submit-label="Saving">Save Branch</button></div>
</form>
@endsection
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush
