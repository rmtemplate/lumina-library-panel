@props(['align' => 'right'])
<details class="relative inline-block text-left" data-action-menu>
    <summary class="list-none cursor-pointer rounded-lg border border-[#c5c6ce] bg-white p-2 text-[#44474d] shadow-sm transition duration-150 hover:bg-[#f5f3f6] hover:text-[#000000] focus:outline-none focus:ring-4 focus:ring-[#000000]/10 [&::-webkit-details-marker]:hidden">
        <x-admin.icon name="more_vert" class="h-5 w-5" />
    </summary>
    <div class="action-menu-panel {{ $align === 'right' ? 'right-0' : 'left-0' }}">
        {{ $slot }}
    </div>
</details>
