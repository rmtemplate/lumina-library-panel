@props(['label', 'value', 'icon' => 'dashboard', 'tone' => 'primary', 'delta' => null])
@php
    $tones = [
        'primary' => ['icon' => 'bg-[#d7e3ff] text-[#081b37]', 'border' => 'hover:border-[#000000]'],
        'secondary' => ['icon' => 'bg-[#6cf8bb] text-[#002113]', 'border' => 'hover:border-[#006c49]'],
        'info' => ['icon' => 'bg-[#cee5fc] text-[#004b6f]', 'border' => 'hover:border-[#2a9ad9]'],
        'warning' => ['icon' => 'bg-amber-100 text-amber-700', 'border' => 'hover:border-amber-400'],
        'danger' => ['icon' => 'bg-[#ffdad6] text-[#93000a]', 'border' => 'hover:border-[#ba1a1a]'],
    ];
    $toneConfig = $tones[$tone] ?? $tones['primary'];
@endphp
<div {{ $attributes->merge(['class' => 'stat-tile '.$toneConfig['border']]) }}>
    <div class="flex items-start justify-between gap-3"><span class="{{ $toneConfig['icon'] }} rounded-lg p-2"><x-admin.icon :name="$icon" class="h-6 w-6" /></span>@if($delta)<span class="rounded-full bg-white px-2 py-1 text-[11px] font-bold text-[#44474d] ring-1 ring-[#c5c6ce]">{{ $delta }}</span>@endif</div>
    <div class="mt-6"><p class="lumina-label">{{ $label }}</p><h3 class="mt-1 text-2xl font-bold tracking-[-0.02em] text-[#000000]">{{ $value }}</h3></div>
</div>
