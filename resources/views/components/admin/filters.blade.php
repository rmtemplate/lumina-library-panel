@props(['action' => url()->current(), 'searchPlaceholder' => 'Search data'])
<form method="GET" action="{{ $action }}" {{ $attributes->merge(['class' => 'mb-5 rounded-xl border border-[#c5c6ce] bg-[#f5f3f6] p-4 shadow-[0_8px_20px_rgba(3,22,50,0.04)]']) }}>
    <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto]">
        <div class="relative min-w-0">
            <x-admin.icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[#75777e]" />
            <input name="search" value="{{ request('search') }}" placeholder="{{ $searchPlaceholder }}" class="form-input pl-11">
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center lg:justify-end">
            {{ $slot }}
            <select name="per_page" class="form-select w-full sm:w-32" title="Page size">
                @foreach([10,20,30,50,100] as $size)<option value="{{ $size }}" @selected((int)request('per_page', 20) === $size)>{{ $size }} rows</option>@endforeach
            </select>
            <button class="btn-primary"><x-admin.icon name="tune" class="h-4 w-4" />Filter</button>
            <a href="{{ $action }}" class="btn-outline">Reset</a>
            <button type="button" onclick="window.print()" class="btn-outline"><x-admin.icon name="file_download" class="h-4 w-4" />Export</button>
        </div>
    </div>
</form>
