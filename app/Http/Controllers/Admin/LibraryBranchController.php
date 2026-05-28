<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LibraryBranchRequest;
use App\Models\LibraryBranch;
use App\Services\Admin\BranchAccessService;
use App\Services\Admin\MasterDataService;
use App\Services\Admin\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LibraryBranchController extends Controller
{
    public function index(Request $request, BranchAccessService $access): View
    {
        $query = LibraryBranch::withCount(['bookItems', 'racks', 'users', 'borrowings']);
        $access->scope($query, $request->user(), 'id');
        $branches = $query->when($request->search, fn ($q, $s) => $q->where(fn ($sub) => $sub->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%")))->orderBy('name')->paginate($request->integer('per_page', 20))->withQueryString();
        return view('admin.branches.index', compact('branches'));
    }
    public function create(Request $request): View { abort_unless($request->user()->isSuperAdmin(), 403); return view('admin.branches.create'); }
    public function store(LibraryBranchRequest $request, MasterDataService $service, MediaUploadService $media): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validated();
        if ($path = $media->store($request->file('image_file'), 'branches')) {
            $data['image_path'] = $path;
            $data['image_is_external'] = false;
        }
        $branch = $service->create(LibraryBranch::class, $data + ['is_active' => $request->boolean('is_active', true)], $request->user()->id);
        return redirect()->route('admin.branches.index')->with('success', "Branch {$branch->name} berhasil dibuat.");
    }
    public function show(Request $request, LibraryBranch $branch, BranchAccessService $access): View { $access->assertCanAccessBranch($request->user(), $branch->id); return view('admin.branches.show', ['branch' => $branch->load(['bookItems.book', 'racks', 'users', 'borrowings'])->loadCount(['bookItems', 'racks', 'users', 'borrowings'])]); }
    public function edit(Request $request, LibraryBranch $branch, BranchAccessService $access): View { abort_unless($request->user()->isSuperAdmin(), 403); return view('admin.branches.edit', compact('branch')); }
    public function update(LibraryBranchRequest $request, LibraryBranch $branch, MasterDataService $service, MediaUploadService $media): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validated();
        if ($path = $media->store($request->file('image_file'), 'branches')) {
            $data['image_path'] = $path;
            $data['image_is_external'] = false;
        }
        $service->update($branch, $data + ['is_active' => $request->boolean('is_active', true)], $request->user()->id);
        return redirect()->route('admin.branches.index')->with('success', "Branch {$branch->name} berhasil diperbarui.");
    }
    public function destroy(Request $request, LibraryBranch $branch, MasterDataService $service): RedirectResponse { abort_unless($request->user()->isSuperAdmin(), 403); $service->delete($branch, $request->user()->id, 'borrowings', 'Branch tidak dapat dihapus karena masih memiliki transaksi.'); return redirect()->route('admin.branches.index')->with('success', "Branch {$branch->name} berhasil dihapus."); }
}
