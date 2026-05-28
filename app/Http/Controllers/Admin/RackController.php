<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RackRequest;
use App\Models\Rack;
use App\Services\Admin\BranchAccessService;
use App\Services\Admin\LookupService;
use App\Services\Admin\MasterDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RackController extends Controller
{
    public function index(Request $request, LookupService $lookup, BranchAccessService $access): View
    {
        $query = Rack::with('branch')->withCount('items');
        $access->scope($query, $request->user(), 'branch_id');
        $racks = $query->when($request->search, fn ($q, $s) => $q->where(fn ($sub) => $sub->where('rack_code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%")))->when($request->branch_id, fn ($q, $branch) => $q->where('branch_id', $access->normalizeBranchId($request->user(), $branch)))->orderBy('rack_code')->paginate($request->integer('per_page', 20))->withQueryString();
        return view('admin.racks.index', ['racks' => $racks, 'branches' => $lookup->activeBranches($request->user())]);
    }
    public function create(Request $request, LookupService $lookup): View { return view('admin.racks.create', ['branches' => $lookup->activeBranches($request->user())]); }
    public function store(RackRequest $request, MasterDataService $service, BranchAccessService $access): RedirectResponse
    {
        $data = $request->validated(); $branchId = $access->requireBranchId($request->user(), $data['branch_id'] ?? null); $data['branch_id'] = $branchId; $data['library_branch_id'] = $branchId;
        $rack = $service->create(Rack::class, $data + ['is_active' => $request->boolean('is_active', true), 'is_basement' => $request->boolean('is_basement', false), 'capacity' => $request->integer('capacity', 0)], $request->user()->id);
        return redirect()->route('admin.racks.index')->with('success', "Rak {$rack->rack_code} berhasil dibuat.");
    }
    public function edit(Request $request, Rack $rack, LookupService $lookup, BranchAccessService $access): View { $access->assertCanAccessBranch($request->user(), $rack->branch_id); return view('admin.racks.edit', ['rack' => $rack, 'branches' => $lookup->activeBranches($request->user())]); }
    public function update(RackRequest $request, Rack $rack, MasterDataService $service, BranchAccessService $access): RedirectResponse
    {
        $access->assertCanAccessBranch($request->user(), $rack->branch_id); $data = $request->validated(); $branchId = $access->requireBranchId($request->user(), $data['branch_id'] ?? null); $data['branch_id'] = $branchId; $data['library_branch_id'] = $branchId;
        $service->update($rack, $data + ['is_active' => $request->boolean('is_active', true), 'is_basement' => $request->boolean('is_basement', false), 'capacity' => $request->integer('capacity', 0)], $request->user()->id);
        return redirect()->route('admin.racks.index')->with('success', "Rak {$rack->rack_code} berhasil diperbarui.");
    }
    public function destroy(Request $request, Rack $rack, MasterDataService $service, BranchAccessService $access): RedirectResponse
    {
        $access->assertCanAccessBranch($request->user(), $rack->branch_id); $service->delete($rack, $request->user()->id, 'items', 'Rak tidak dapat dihapus karena masih memiliki copy buku aktif.'); return redirect()->route('admin.racks.index')->with('success', "Rak {$rack->rack_code} berhasil dihapus.");
    }
}
