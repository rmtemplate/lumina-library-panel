@props([
    'latitude' => null,
    'longitude' => null,
    'address' => '',
    'height' => '360px',
    'readonly' => false,
])
@php
    $lat = old('latitude', $latitude) ?: -6.2000000;
    $lng = old('longitude', $longitude) ?: 106.8166660;
@endphp
<div class="space-y-4" data-map-picker data-lat="{{ $lat }}" data-lng="{{ $lng }}" data-readonly="{{ $readonly ? '1' : '0' }}">
    @unless($readonly)
        <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto_auto]">
            <div class="relative">
                <x-admin.icon name="magnifying-glass" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[#75777e]" />
                <input type="text" data-map-search value="{{ old('address', $address) }}" placeholder="Cari alamat/lokasi branch..." class="form-input pl-11">
            </div>
            <button type="button" data-map-search-button class="btn-outline cursor-pointer"><x-admin.icon name="map" class="h-4 w-4" />Search</button>
            <button type="button" data-map-current-button class="btn-secondary cursor-pointer"><x-admin.icon name="pin" class="h-4 w-4" />Use Current</button>
        </div>
        <div class="rounded-xl border border-[#c5c6ce]/70 bg-[#f5f3f6] px-4 py-3 text-sm text-[#44474d]">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined mt-0.5 text-[#006c49]">location_on</span>
                <div>
                    <p class="font-semibold text-[#191c1d]">Pin lokasi aktif</p>
                    <p class="mt-1 leading-6">Geser pin merah pada map atau klik titik lokasi baru. Latitude dan longitude akan terisi otomatis.</p>
                    <p class="mt-1 text-xs font-semibold text-[#006c49]" data-map-status>Pin siap dipindahkan.</p>
                </div>
            </div>
        </div>
    @endunless
    <div class="relative overflow-hidden rounded-xl border border-[#c5c6ce] bg-[#f5f3f6]">
        <div data-map-canvas style="height: {{ $height }}"></div>
        <div class="pointer-events-none absolute left-4 top-4 z-[500] rounded-full bg-white/95 px-3 py-2 text-xs font-semibold text-[#191c1d] shadow-lg shadow-slate-900/10 ring-1 ring-[#c5c6ce]/70">
            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#ba1a1a]"></span>{{ $readonly ? 'Pin lokasi branch' : 'Drag pin untuk menentukan titik' }}</span>
        </div>
    </div>
    @unless($readonly)
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label">Latitude</label>
                <input name="latitude" data-map-lat value="{{ old('latitude', $latitude) }}" class="form-input" readonly>
            </div>
            <div>
                <label class="form-label">Longitude</label>
                <input name="longitude" data-map-lng value="{{ old('longitude', $longitude) }}" class="form-input" readonly>
            </div>
        </div>
    @endunless
</div>
