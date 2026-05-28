<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BannerRequest;
use App\Models\Banner;
use App\Services\Admin\BranchAccessService;
use App\Services\Admin\LookupService;
use App\Services\Admin\MasterDataService;
use App\Services\Admin\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(Request $request, BranchAccessService $access): View
    {
        $query = Banner::with('branch');
        $access->scope($query, $request->user(), 'branch_id');
        $banners = $query->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))->orderBy('sort_order')->paginate($request->integer('per_page', 20))->withQueryString();
        return view('admin.banners.index', compact('banners'));
    }
    public function create(Request $request, LookupService $lookup): View
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Hanya Super Admin yang dapat membuat banner.');

        return view('admin.banners.create', [
            'branches' => $lookup->activeBranches($request->user()),
        ]);
    }
    public function store(BannerRequest $request, MasterDataService $service, MediaUploadService $media, BranchAccessService $access): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Hanya Super Admin yang dapat membuat banner.');

        $data = $request->validated();
        $data['branch_id'] = $access->normalizeBranchId($request->user(), $data['branch_id'] ?? null);
        if ($data['branch_id']) $access->assertCanAccessBranch($request->user(), $data['branch_id']);
        if ($path = $media->store($request->file('image_file'), 'banners')) { $data['image_path'] = $path; $data['image_is_external'] = false; }
        $banner = $service->create(Banner::class, $data + ['is_active' => $request->boolean('is_active', true), 'sort_order' => $request->integer('sort_order', 0)], $request->user()->id);
        return redirect()->route('admin.banners.index')->with('success', "Banner {$banner->title} berhasil dibuat.");
    }
    public function edit(Request $request, Banner $banner, LookupService $lookup, BranchAccessService $access): View { if ($banner->branch_id) $access->assertCanAccessBranch($request->user(), $banner->branch_id); return view('admin.banners.edit', ['banner' => $banner, 'branches' => $lookup->activeBranches($request->user())]); }
    public function update(BannerRequest $request, Banner $banner, MasterDataService $service, MediaUploadService $media, BranchAccessService $access): RedirectResponse
    {
        if ($banner->branch_id) $access->assertCanAccessBranch($request->user(), $banner->branch_id);
        $data = $request->validated(); $data['branch_id'] = $access->normalizeBranchId($request->user(), $data['branch_id'] ?? null);
        if ($data['branch_id']) $access->assertCanAccessBranch($request->user(), $data['branch_id']);
        if ($path = $media->store($request->file('image_file'), 'banners')) { $data['image_path'] = $path; $data['image_is_external'] = false; }
        $service->update($banner, $data + ['is_active' => $request->boolean('is_active', true), 'sort_order' => $request->integer('sort_order', 0)], $request->user()->id);
        return redirect()->route('admin.banners.index')->with('success', "Banner {$banner->title} berhasil diperbarui.");
    }
    public function destroy(Request $request, Banner $banner, MasterDataService $service, BranchAccessService $access): RedirectResponse { if ($banner->branch_id) $access->assertCanAccessBranch($request->user(), $banner->branch_id); $service->delete($banner, $request->user()->id); return redirect()->route('admin.banners.index')->with('success', "Banner {$banner->title} berhasil dihapus."); }
}
