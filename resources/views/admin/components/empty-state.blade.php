@props(['title' => 'No data found', 'description' => 'Try changing filters or create a new record.', 'icon' => 'sparkles'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-14 text-center']) }}>
    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#031632]/5 text-[#031632] ring-1 ring-[#031632]/10"><x-admin.icon :name="$icon" class="h-7 w-7" /></div>
    <h3 class="text-base font-bold text-[#191c1d]">{{ $title }}</h3>
    <p class="mt-2 max-w-md text-sm leading-6 text-[#44474d]">{{ $description }}</p>
</div>
