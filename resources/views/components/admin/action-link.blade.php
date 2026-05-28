@props(['href' => '#', 'icon' => 'eye', 'danger' => false])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex w-full cursor-pointer items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition '.($danger ? 'text-red-700 hover:bg-red-50' : 'text-[#44474d] hover:bg-[#f3f4f5] hover:text-[#031632]')]) }}>
    <x-admin.icon :name="$icon" class="h-4 w-4" />{{ $slot }}
</a>
