@props(['paginator'])
@if($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
    <div class="flex flex-col gap-3 border-t border-[#c5c6ce] bg-[#f5f3f6] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-[#44474d]">
            Showing <span class="font-semibold text-[#191c1d]">{{ $paginator->firstItem() ?? 0 }}</span> to <span class="font-semibold text-[#191c1d]">{{ $paginator->lastItem() ?? 0 }}</span> of <span class="font-semibold text-[#191c1d]">{{ $paginator->total() }}</span> data · Page {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        </div>
        <div class="pagination-wrap">{{ $paginator->onEachSide(1)->links() }}</div>
    </div>
@endif
