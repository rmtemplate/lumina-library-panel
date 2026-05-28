<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookItemRequest;
use App\Models\BookItem;
use App\Services\Admin\BookItemService;
use App\Services\Admin\BranchAccessService;
use App\Services\Admin\LookupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookItemController extends Controller
{
    public function index(Request $request, BookItemService $service, LookupService $lookup): View
    {
        return view('admin.book-items.index', [
            'items' => $service->paginate($request->only(['search', 'branch_id', 'book_id', 'status', 'per_page']), $request->user()),
            'branches' => $lookup->activeBranches($request->user()),
            'books' => $lookup->activeBooks(),
            'statuses' => BookItemStatus::cases(),
        ]);
    }

    public function create(Request $request, LookupService $lookup): View
    {
        return view('admin.book-items.create', [
            'item' => null,
            'branches' => $lookup->activeBranches($request->user()),
            'books' => $lookup->activeBooks(),
            'racks' => $lookup->activeRacks($request->user()),
            'statuses' => BookItemStatus::cases(),
        ]);
    }

    public function store(BookItemRequest $request, BookItemService $service): RedirectResponse
    {
        $item = $service->create($request->validated(), $request->user());
        return redirect()->route('admin.book-items.index')->with('success', "Copy buku {$item->item_code} berhasil dibuat.");
    }

    public function edit(Request $request, BookItem $bookItem, LookupService $lookup, BranchAccessService $access): View
    {
        $access->assertCanAccessBranch($request->user(), $bookItem->branch_id);
        return view('admin.book-items.edit', [
            'item' => $bookItem->load(['book', 'branch', 'rack']),
            'branches' => $lookup->activeBranches($request->user()),
            'books' => $lookup->activeBooks(),
            'racks' => $lookup->activeRacks($request->user(), $bookItem->branch_id),
            'statuses' => BookItemStatus::cases(),
        ]);
    }

    public function update(BookItemRequest $request, BookItem $bookItem, BookItemService $service): RedirectResponse
    {
        $service->update($bookItem, $request->validated(), $request->user());
        return redirect()->route('admin.book-items.index')->with('success', "Copy buku {$bookItem->item_code} berhasil diperbarui.");
    }

    public function destroy(Request $request, BookItem $bookItem, BookItemService $service): RedirectResponse
    {
        $service->delete($bookItem, $request->user());
        return redirect()->route('admin.book-items.index')->with('success', "Copy buku {$bookItem->item_code} berhasil dihapus.");
    }
}
