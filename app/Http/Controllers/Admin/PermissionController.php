<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PermissionRequest;
use App\Models\Permission;
use App\Services\Admin\MasterDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = in_array($request->integer('per_page', 20), [10, 20, 30, 50, 100], true) ? $request->integer('per_page', 20) : 20;
        $permissions = Permission::withCount('roles')
            ->when($request->search, fn ($q, $s) => $q->where(fn ($sub) => $sub->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%")))
            ->when($request->module, fn ($q, $module) => $q->where('module', $module))
            ->orderBy('module')->orderBy('code')->paginate($perPage)->withQueryString();
        $modules = Permission::query()->select('module')->distinct()->orderBy('module')->pluck('module');
        return view('admin.permissions.index', compact('permissions', 'modules'));
    }

    public function create(): View { return view('admin.permissions.create'); }

    public function store(PermissionRequest $request, MasterDataService $service): RedirectResponse
    {
        $permission = $service->create(Permission::class, $request->validated() + ['is_active' => $request->boolean('is_active', true)], $request->user()->id);
        return redirect()->route('admin.permissions.index')->with('success', "Permission {$permission->name} berhasil dibuat.");
    }

    public function edit(Permission $permission): View { return view('admin.permissions.edit', compact('permission')); }

    public function update(PermissionRequest $request, Permission $permission, MasterDataService $service): RedirectResponse
    {
        $service->update($permission, $request->validated() + ['is_active' => $request->boolean('is_active', true)], $request->user()->id);
        return redirect()->route('admin.permissions.index')->with('success', "Permission {$permission->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, Permission $permission, MasterDataService $service): RedirectResponse
    {
        $service->delete($permission, $request->user()->id, 'roles', 'Permission tidak dapat dihapus karena masih digunakan role.');
        return redirect()->route('admin.permissions.index')->with('success', "Permission {$permission->name} berhasil dihapus.");
    }
}
